<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_certledger\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use local_certledger\registry;
use stdClass;

/**
 * Tests for the Privacy API implementation.
 *
 * @package    local_certledger
 * @category   test
 * @copyright  2026 Giuliano Delfino
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends provider_testcase {
    /** @var stdClass learner with a certificate */
    private stdClass $learner;

    /** @var stdClass manager who annulled a certificate */
    private stdClass $manager;

    /**
     * A learner with two certificates, one of them annulled by a manager.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();
        // On PostgreSQL the test runs inside a transaction, which holds back the events for external observers
        // such as ours until a commit that never comes: reset the slow way instead.
        $this->preventResetByRollback();
        set_config('enabledsince', 0, 'local_certledger');
        $course = $this->getDataGenerator()->create_course();
        $this->learner = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->manager = $this->getDataGenerator()->create_user();
        foreach (['First', 'Second'] as $name) {
            $customcert = $this->getDataGenerator()->create_module('customcert', ['course' => $course->id, 'name' => $name]);
            if (class_exists(\mod_customcert\service\certificate_issue_service::class)) {
                $issueid = \mod_customcert\service\certificate_issue_service::create()
                    ->issue_certificate((int)$customcert->id, (int)$this->learner->id);
            } else {
                $issueid = \mod_customcert\certificate::issue_certificate((int)$customcert->id, (int)$this->learner->id);
            }
        }
        $last = $DB->get_record(registry::TABLE, ['issueid' => $issueid], '*', MUST_EXIST);
        registry::annul($last->id, 'Issued by mistake', $this->manager->id);
    }

    public function test_get_metadata(): void {
        $collection = provider::get_metadata(new collection('local_certledger'));
        $this->assertCount(2, $collection->get_collection());
    }

    public function test_get_contexts_for_userid(): void {
        $system = \context_system::instance();
        $this->assertEquals([$system->id], provider::get_contexts_for_userid($this->learner->id)->get_contextids());
        $this->assertEquals([$system->id], provider::get_contexts_for_userid($this->manager->id)->get_contextids());
        $other = $this->getDataGenerator()->create_user();
        $this->assertSame([], provider::get_contexts_for_userid($other->id)->get_contextids());
    }

    public function test_get_users_in_context(): void {
        $userlist = new userlist(\context_system::instance(), 'local_certledger');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();
        sort($userids);
        $expected = [(int)$this->learner->id, (int)$this->manager->id];
        sort($expected);
        $this->assertSame($expected, array_map('intval', $userids));
    }

    public function test_export_user_data(): void {
        $system = \context_system::instance();
        $this->export_context_data_for_user($this->learner->id, $system, 'local_certledger');
        $data = writer::with_context($system)->get_data([get_string('pluginname', 'local_certledger')]);
        $this->assertCount(2, $data->certificates);
        $this->assertSame('Issued by mistake', $data->certificates[1]->statusreason);
        $this->assertNotEmpty($data->certificates[0]->originalcode);
    }

    public function test_delete_keeps_the_register_by_default(): void {
        global $DB;

        $contextlist = new approved_contextlist($this->learner, 'local_certledger', [\context_system::instance()->id]);
        provider::delete_data_for_user($contextlist);
        $this->assertSame(2, $DB->count_records(registry::TABLE, ['userid' => $this->learner->id]));
    }

    public function test_delete_anonymises_when_enabled(): void {
        global $DB;

        set_config('privacyanonymise', 1, 'local_certledger');
        $contextlist = new approved_contextlist($this->learner, 'local_certledger', [\context_system::instance()->id]);
        provider::delete_data_for_user($contextlist);

        $this->assertSame(0, $DB->count_records(registry::TABLE, ['userid' => $this->learner->id]));
        $this->assertSame(2, $DB->count_records(registry::TABLE, ['userid' => 0, 'email' => '']));
        // The annulment made by the manager is untouched: only the learner asked to be deleted.
        $this->assertSame(1, $DB->count_records(registry::TABLE, ['statusby' => $this->manager->id]));
    }

    public function test_delete_data_for_users_anonymises_the_manager(): void {
        global $DB;

        set_config('privacyanonymise', 1, 'local_certledger');
        $userlist = new approved_userlist(\context_system::instance(), 'local_certledger', [$this->manager->id]);
        provider::delete_data_for_users($userlist);

        $this->assertSame(0, $DB->count_records(registry::TABLE, ['statusby' => $this->manager->id]));
        $this->assertSame(2, $DB->count_records(registry::TABLE, ['userid' => $this->learner->id]));
    }

    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        set_config('privacyanonymise', 1, 'local_certledger');
        provider::delete_data_for_all_users_in_context(\context_system::instance());
        $this->assertSame(0, $DB->count_records_select(registry::TABLE, 'userid <> 0 OR statusby <> 0'));
        $this->assertSame(2, $DB->count_records(registry::TABLE));
    }
}
