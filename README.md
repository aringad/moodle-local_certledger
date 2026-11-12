# Certificate Ledger (local_certledger)

Progressive numbering and a permanent register for the certificates issued by
[Custom certificate](https://moodle.org/plugins/mod_customcert) (mod_customcert).

Training providers often have to number their certificates in a single series (for example `CERT-2026-000123`)
and keep a register of every certificate they issued, including the ones later withdrawn. Certificate Ledger adds
this to Custom certificate without modifying it:

- every certificate gets the next number of the series as soon as it is issued, and the number is printed on it;
- the register keeps a snapshot of learner, course and certificate taken at issue time, so it stays accurate even
  when users or courses are later changed or deleted;
- managers can search, filter and export the register, and annul a certificate with a reason;
- a number is never reused, not even after an annulment, a deletion or a restore from an old backup.

## How it works

1. When Custom certificate issues a certificate, the plugin receives its `issue_created` event and assigns the next
   number of the series, for example `CERT-2026-000001`.
   - A database lock makes sure two certificates issued at the same moment never get the same number.
   - Unique indexes on the register are a second guarantee.
2. The code of the certificate is replaced with the number followed by a few random check characters, for example
   `CERT-2026-000001-K7QXM`. Custom certificate prints it with its standard **Code** and **QR code** elements and
   verifies it on its own verification page.
   - The check characters stop anyone from finding other people's certificates by trying consecutive numbers on
     the public verification page.
3. The register stores a snapshot of the data at issue time: learner, email, course, certificate, cohorts and
   date.

Custom certificate is **not modified**: the plugin only uses its public events and its `customcert_issues` table,
so Custom certificate can be upgraded independently.

## Certificate status

| Status | When |
|---|---|
| **Valid** | The certificate exists on the platform and can be downloaded and verified. |
| **Annulled** | Annulled from the register, with a reason. The certificate is deleted from the platform. |
| **Removed from the platform** | Deleted in another way: from the activity report, by deleting the activity or the course, or by a course reset. |

Annulled and removed certificates stay in the register. Before assigning a number the plugin also looks at the codes
already printed on issued certificates, so a printed number is never reused, even if the register was lost or
restored from an older backup.

### Annulment and block

Annulling a certificate deletes it from the platform, **including the copies** made by a course restore. Custom
certificate, however, issues a new certificate (with a new number) as soon as the learner opens the activity again,
or within a minute when automatic issuing or student emails are enabled. The annulment page therefore offers, enabled
by default, a **block**: a technical role ("Certificate blocked (Certificate Ledger)") assigned to the learner in the certificate activity,
which removes the permission to receive the certificate. The block is lifted from the register ("Lift the block"
button on the annulled row). The role is created on first use. The block is also recorded in the register, so if
Moodle drops the role assignment (for example when the learner is unenrolled and enrolled again) the reconciliation
sets it again. Uninstalling the plugin deletes the role and its assignments.

## Where to find it

- **Register**: Site administration → Reports → Certificate ledger (`/local/certledger/index.php`).
  - Filters by year, course and status, and search by number, code, name or email.
  - Export in every enabled format (Excel, CSV, ODS, PDF…).
- **Settings**: Site administration → Plugins → Local plugins → Certificate Ledger.
  - Prefix, yearly restart, digits, check characters, anonymisation on privacy requests.
  - Changes apply only to the certificates issued afterwards.

## Capabilities

| Capability | Default | Allows |
|---|---|---|
| `local/certledger:view` | Manager | Viewing and exporting the register (it contains personal data) |
| `local/certledger:annul` | Manager | Annulling a certificate |

Site administrators always have both. Views, exports, annulments and assigned numbers are logged.

## Setting up a certificate

1. In the certificate template add the **Code** element, which prints the number, and optionally the **QR code**
   element.
2. For automatic issuing on completion, in the Custom certificate activity:
   - add an **access restriction** on the completion of the course activities (or of the course);
   - enable emailing the certificate to students or, from Custom certificate 5.0.8, automatic issuing.
   Otherwise the certificate is issued, and numbered, when the learner downloads it from the activity.

## Scheduled reconciliation

The scheduled task **Certificate ledger reconciliation** runs every 10 minutes and, in this order:

1. **Relinks** a valid or removed certificate whose Custom certificate record disappeared and came back with the same
   code (activity restored from the recycle bin or from a backup): it is valid again, with number, date and snapshot
   unchanged.
2. **Deletes the copies** of annulled certificates (same code), so an annulled certificate never comes back.
3. **Sets again the blocks** whose role assignment Moodle dropped (not for deleted users).
4. **Records with their number** the certificates that already carry a number of the plugin but are missing from the
   register (plugin uninstalled and reinstalled, table restored from an older backup): the printed code never changes.
5. **Numbers** the certificates that did not get a number when they were issued.
6. **Marks as removed** the certificates deleted without an event, for example by a course reset or a privacy deletion.
7. **Writes the number back** on the certificate if something changed its code.

**Copies** of valid certificates, for example from a course restored with user data into another course, are **not**
numbered or recorded: they can still be downloaded with the original code, and the task counts them ("copies left").

Reconciliation errors are written to the task log and shown on the register page until a run without errors; the
page also warns when issued certificates are waiting for a number.

**Known limit.** Numbering happens when the issue event is processed. If the numbering lock cannot be obtained (rare),
a PDF downloaded before the reconciliation carries the random code of Custom certificate; the reconciliation then
replaces it with the number within 10 minutes and keeps the earlier code in the register. Downloading the certificate
again gives the PDF with the number.

## Privacy

The plugin implements the Moodle Privacy API.

- **Export**: a user's data export includes their certificates and the annulments they made.
- **Deletion**: by default the register is **kept intact** after an approved deletion request, as a record that must
  stay complete and verifiable (certificates are often a legal obligation of the training provider). This should be
  stated in the site privacy notice. With the setting "Anonymise on deletion requests" enabled, approved requests
  anonymise first name, last name, email, cohorts and reasons, keeping number, course, dates and status.
- Note that Moodle approves requests for all plugins together; with the Data registry configured, the system context,
  where the register lives, is not included in deletions (standard Moodle behaviour).
- **Uninstall**: before dropping the table the register is saved to
  `moodledata/local_certledger/register-<date>.csv`, which contains personal data. Delete that file when it is no
  longer needed.

## Command line

From the Moodle directory (on Moodle 5.1 and later the web files are in `public/`):

```
# List the certificates not numbered yet (changes nothing)
php public/local/certledger/cli/backfill.php --all
# Number them; those already carrying a number of the plugin are recorded with that number
php public/local/certledger/cli/backfill.php --all --run
```

Numbering certificates that were already issued changes their code: PDFs and QR codes downloaded before no longer
verify, and their learners must download them again. The old code is kept in the register.

## Requirements and compatibility

- Moodle 5.0 or later, Custom certificate (mod_customcert) 5.0 or later. Tested on Moodle 5.0, 5.2 and 5.3 with
  PostgreSQL and MariaDB.
- Uses only stable interfaces: Moodle events, locks, `table_sql`, Privacy API and scheduled tasks; of Custom
  certificate, its public events and the `code` column of `customcert_issues`.
- **Backup**: the register is not part of course backups; include it in the database backup. Restoring the whole
  database from an older backup also takes the register back: the reconciliation records again the numbers assigned
  after the backup, from the issued certificates.
- **Public verification**: an annulled certificate no longer verifies on the Custom certificate verification page,
  exactly like an unknown code; the reason is only visible in the register.

## Translations

The plugin package contains only English. Translations are managed on [AMOS](https://lang.moodle.org); the Italian
translation is in `translations/it` in the source repository, ready to be contributed.

## License

2026 Giuliano Delfino

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
