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

namespace local_certledger;

use advanced_testcase;
use moodle_exception;
use stdClass;

/**
 * Tests for the numbering and the register.
 *
 * @package    local_certledger
 * @category   test
 * @copyright  2026 Giuliano Delfino
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(registry::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observer::class)]
final class registry_test extends advanced_testcase {
    /** @var stdClass course */
    private stdClass $course;

    /** @var stdClass Custom certificate activity */
    private stdClass $customcert;

    /**
     * Course with a certificate activity, numbering with prefix TEST from the start of the test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        set_config('prefix', 'TEST', 'local_certledger');
        set_config('yearlyreset', 1, 'local_certledger');
        set_config('enabledsince', 0, 'local_certledger');
        $this->course = $this->getDataGenerator()->create_course();
        $this->customcert = $this->getDataGenerator()->create_module('customcert', ['course' => $this->course->id]);
    }

    /**
     * Issues the certificate to a user the way mod_customcert does, event included.
     *
     * @param int $userid
     * @return int issue id
     */
    private function issue(int $userid): int {
        if (class_exists(\mod_customcert\service\certificate_issue_service::class)) {
            return \mod_customcert\service\certificate_issue_service::create()
                ->issue_certificate((int)$this->customcert->id, $userid);
        }
        return \mod_customcert\certificate::issue_certificate((int)$this->customcert->id, $userid);
    }

    /**
     * Writes an issue straight into the table, without the event: the observer does not see it.
     *
     * @param int $userid
     * @param string $code
     * @return int issue id
     */
    private function insert_issue(int $userid, string $code): int {
        global $DB;
        return $DB->insert_record('customcert_issues', (object)[
            'userid' => $userid, 'customcertid' => $this->customcert->id, 'code' => $code,
            'emailed' => 0, 'studentemailed' => 0, 'timecreated' => time(),
        ]);
    }

    /**
     * Creates a user enrolled in the course as a student.
     *
     * @return stdClass
     */
    private function student(): stdClass {
        return $this->getDataGenerator()->create_and_enrol($this->course, 'student');
    }

    public function test_issued_certificates_get_consecutive_numbers(): void {
        global $DB;

        $year = registry::year_of(time());
        $first = $this->issue($this->student()->id);
        // The observer numbers silently: a warning means it deferred the work to the scheduled task.
        $this->assertDebuggingNotCalled();
        $second = $this->issue($this->student()->id);

        $one = $DB->get_record(registry::TABLE, ['issueid' => $first], '*', MUST_EXIST);
        $two = $DB->get_record(registry::TABLE, ['issueid' => $second], '*', MUST_EXIST);
        $this->assertSame("TEST-{$year}-000001", $one->certnumber);
        $this->assertSame("TEST-{$year}-000002", $two->certnumber);
        $this->assertMatchesRegularExpression("/^TEST-{$year}-000001-[A-Z2-9]{5}$/", $one->certcode);
        // The number is printed through the code of the issue; the random code of mod_customcert is kept.
        $this->assertSame($one->certcode, $DB->get_field('customcert_issues', 'code', ['id' => $first]));
        $this->assertNotEmpty($one->origcode);
        $this->assertEquals(registry::STATUS_VALID, $one->status);
    }

    public function test_register_issue_is_idempotent(): void {
        global $DB;

        $issueid = $this->issue($this->student()->id);
        $record = registry::register_issue($issueid);
        $this->assertEquals($record->id, registry::register_issue($issueid)->id);
        $this->assertSame(1, $DB->count_records(registry::TABLE));
    }

    public function test_parse_code(): void {
        $this->assertSame('TEST-2026-000012', registry::parse_code('TEST-2026-000012-K7QXM')->number);
        $this->assertSame(12, registry::parse_code('TEST-2026-000012')->seq);
        $this->assertSame('AB1', registry::parse_code('AB1-0042')->scope);
        // The "digits with hyphens" and random codes of mod_customcert are not ours.
        $this->assertNull(registry::parse_code('0123-4567-8901'));
        $this->assertNull(registry::parse_code('Wg3lgtbPdR'));
        $this->assertNull(registry::parse_code(null));
    }

    public function test_reconcile_adopts_numbers_already_printed(): void {
        global $DB;

        $year = registry::year_of(time());
        // A certificate numbered before the register was lost: its number is kept, the next one follows it.
        $old = $this->insert_issue($this->student()->id, "TEST-{$year}-000007-ABCDE");
        $result = registry::reconcile();
        $this->assertSame(1, $result->adopted);
        $this->assertSame("TEST-{$year}-000007-ABCDE", $DB->get_field(registry::TABLE, 'certcode', ['issueid' => $old]));

        $new = $this->issue($this->student()->id);
        $this->assertSame("TEST-{$year}-000008", $DB->get_field(registry::TABLE, 'certnumber', ['issueid' => $new]));
    }

    public function test_reconcile_numbers_issues_the_observer_missed(): void {
        global $DB;

        $issueid = $this->insert_issue($this->student()->id, 'RANDOMCODE');
        $result = registry::reconcile();
        $this->assertSame(1, $result->registered);
        $record = $DB->get_record(registry::TABLE, ['issueid' => $issueid], '*', MUST_EXIST);
        $this->assertSame('RANDOMCODE', $record->origcode);
        $this->assertSame($record->certcode, $DB->get_field('customcert_issues', 'code', ['id' => $issueid]));
    }

    public function test_annul_with_block(): void {
        global $DB;

        $user = $this->student();
        $manager = $this->getDataGenerator()->create_user();
        $issueid = $this->issue($user->id);
        $record = $DB->get_record(registry::TABLE, ['issueid' => $issueid]);

        registry::annul($record->id, 'Wrong learner', $manager->id, true);

        $record = $DB->get_record(registry::TABLE, ['id' => $record->id]);
        $this->assertEquals(registry::STATUS_ANNULLED, $record->status);
        $this->assertSame('Wrong learner', $record->statusreason);
        $this->assertEquals($manager->id, $record->statusby);
        $this->assertNotEmpty($record->timeblocked);
        $this->assertFalse($DB->record_exists('customcert_issues', ['id' => $issueid]));
        $context = \context_module::instance($this->customcert->cmid);
        $this->assertFalse(has_capability('mod/customcert:receiveissue', $context, $user));
    }

    public function test_annul_twice_fails(): void {
        global $DB;

        $record = $DB->get_record(registry::TABLE, ['issueid' => $this->issue($this->student()->id)]);
        registry::annul($record->id, 'Duplicate', get_admin()->id);
        $this->expectException(moodle_exception::class);
        registry::annul($record->id, 'Duplicate', get_admin()->id);
    }

    public function test_annul_deletes_copies_and_the_original_issue(): void {
        global $DB;

        $issueid = $this->issue($this->student()->id);
        $record = $DB->get_record(registry::TABLE, ['issueid' => $issueid]);
        // A copy with the same code, as left by a course restore, and the original with a code that drifted.
        $copy = $this->insert_issue($this->student()->id, $record->certcode);
        $DB->set_field('customcert_issues', 'code', 'DRIFTED', ['id' => $issueid]);

        registry::annul($record->id, 'Annulled', get_admin()->id);

        $this->assertFalse($DB->record_exists('customcert_issues', ['id' => $issueid]));
        $this->assertFalse($DB->record_exists('customcert_issues', ['id' => $copy]));
    }

    public function test_reconcile_deletes_copies_of_annulled_certificates(): void {
        global $DB;

        $record = $DB->get_record(registry::TABLE, ['issueid' => $this->issue($this->student()->id)]);
        registry::annul($record->id, 'Annulled', get_admin()->id);
        $copy = $this->insert_issue($this->student()->id, $record->certcode);

        $result = registry::reconcile();
        $this->assertSame(1, $result->annulledcopies);
        $this->assertFalse($DB->record_exists('customcert_issues', ['id' => $copy]));
    }

    public function test_reconcile_restores_a_dropped_block(): void {
        global $DB;

        $record = $DB->get_record(registry::TABLE, ['issueid' => $this->issue($this->student()->id)]);
        registry::annul($record->id, 'Annulled', get_admin()->id, true);
        role_unassign_all(['component' => 'local_certledger', 'itemid' => $record->id]);

        $result = registry::reconcile();
        $this->assertSame(1, $result->reblocked);
        $this->assertSame(0, $result->failed);
        $this->assertTrue($DB->record_exists('role_assignments', ['component' => 'local_certledger', 'itemid' => $record->id]));
    }

    public function test_reconcile_ignores_blocks_of_deleted_users(): void {
        global $DB;

        $user = $this->student();
        $record = $DB->get_record(registry::TABLE, ['issueid' => $this->issue($user->id)]);
        registry::annul($record->id, 'Annulled', get_admin()->id, true);
        delete_user($user);

        $result = registry::reconcile();
        $this->assertSame(0, $result->failed);
        $this->assertSame(0, $result->reblocked);
        $this->assertSame([], $result->errors);
    }

    public function test_reconcile_marks_certificates_deleted_without_event(): void {
        global $DB;

        $issueid = $this->issue($this->student()->id);
        $DB->delete_records('customcert_issues', ['id' => $issueid]);

        $result = registry::reconcile();
        $this->assertSame(1, $result->removed);
        $this->assertEquals(registry::STATUS_REMOVED, $DB->get_field(registry::TABLE, 'status', ['issueid' => $issueid]));
    }

    public function test_unregistered_own_issues_are_limited(): void {
        $year = registry::year_of(time());
        // Codes of mod_customcert with hyphens are skipped, ours are returned up to the limit.
        $this->insert_issue($this->student()->id, '0123-4567-8901');
        $this->insert_issue($this->student()->id, '2345-6789-0123');
        $this->insert_issue($this->student()->id, "TEST-{$year}-000003-ABCDE");
        $this->insert_issue($this->student()->id, "TEST-{$year}-000004-FGHJK");

        $issues = registry::get_unregistered_issues(0, 1, true);
        $this->assertCount(1, $issues);
        $this->assertSame("TEST-{$year}-000003-ABCDE", reset($issues)->code);
        $this->assertCount(2, registry::get_unregistered_issues(0, 0, true));
    }
}
