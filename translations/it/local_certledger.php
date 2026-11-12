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

/**
 * Stringhe in italiano.
 *
 * @package    local_certledger
 * @copyright  2026 Giuliano Delfino
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['all'] = 'Tutti';
$string['annul'] = 'Annulla attestato';
$string['annulcertificate'] = 'Annullamento dell\'attestato {$a}';
$string['annulled'] = 'Attestato {$a} annullato.';
$string['annulreason'] = 'Motivo dell\'annullamento';
$string['annulwarning'] = 'L\'attestato verrà segnato come annullato nel registro ed eliminato dalla piattaforma, comprese le eventuali copie create da un ripristino del corso: il corsista non potrà più scaricarlo e il suo codice non sarà più verificabile. Il numero non viene mai riassegnato.';
$string['anonymised'] = 'Dati anonimizzati';
$string['applyfilters'] = 'Filtra';
$string['backtoregister'] = 'Torna al registro';
$string['blocked'] = 'Bloccato';
$string['blockedinfo'] = 'L\'attestato è annullato e il corsista non può riceverlo di nuovo da questa attività. Togliendo il blocco, Certificato personalizzato potrà emettere un nuovo attestato, con un nuovo numero, se il corsista soddisfa i requisiti.';
$string['blockreissue'] = 'Impedisci una nuova emissione al corsista';
$string['blockreissue_desc'] = 'Consigliato. Senza il blocco, Certificato personalizzato emette un nuovo attestato con un nuovo numero appena il corsista riapre l\'attività, o entro un minuto se l\'emissione è automatica. Il blocco si toglie dal registro.';
$string['blockroledesc'] = 'Ruolo tecnico del Registro attestati: assegnato nell\'attività a chi ha un attestato annullato, impedisce a Certificato personalizzato di emetterne un altro. Non assegnare a mano.';
$string['blockrolename'] = 'Attestato bloccato (Registro attestati)';
$string['certcode'] = 'Codice sull\'attestato';
$string['certledger:annul'] = 'Annullare attestati del registro';
$string['certledger:view'] = 'Consultare ed esportare il registro attestati';
$string['certname'] = 'Attestato';
$string['certnumber'] = 'Numero';
$string['codegenerationfailed'] = 'Impossibile generare un codice univoco per l\'attestato.';
$string['cohorts'] = 'Cooperativa / coorte';
$string['confirmannul'] = 'Conferma l\'annullamento';
$string['digits'] = 'Cifre';
$string['digits_desc'] = 'Numero minimo di cifre del progressivo, completato con zeri iniziali (es. 6 cifre: 000001). Cambiarlo a numerazione avviata cambia solo la scrittura dei numeri successivi.';
$string['downloadpdf'] = 'PDF';
$string['downloadpdfof'] = 'Scarica il PDF dell\'attestato {$a}';
$string['erroradopt'] = 'Registrazione del rilascio {$a} con il suo numero';
$string['errorannulledcopy'] = 'Eliminazione del rilascio {$a}, copia di un attestato annullato';
$string['errorblock'] = 'Blocco di {$a}';
$string['errornotadopted'] = 'Rilascio {$a->issueid}: il suo numero {$a->code} è già nel registro con un altro codice, oppure la sua attività non esiste più';
$string['errornumber'] = 'Numerazione del rilascio {$a}';
$string['errorrelink'] = 'Ricollegamento di {$a}';
$string['eventcertificateannulled'] = 'Attestato annullato';
$string['eventnumberassigned'] = 'Numero assegnato all\'attestato';
$string['eventregisterexported'] = 'Registro attestati esportato';
$string['eventregisterviewed'] = 'Registro attestati consultato';
$string['filteryear'] = 'Anno';
$string['learner'] = 'Corsista';
$string['locktimeout'] = 'Impossibile ottenere il blocco della numerazione, riprovare.';
$string['nocertificates'] = 'Nessun attestato corrisponde ai filtri.';
$string['notvalid'] = 'Questo attestato non è più valido e non può essere annullato.';
$string['numbering'] = 'Numerazione';
$string['numbering_desc'] = 'Ogni attestato emesso dall\'attività Certificato personalizzato riceve un numero progressivo al momento del rilascio. Il numero viene registrato nel registro e stampato sull\'attestato dagli elementi "Codice" e "Codice QR" del modello. Le modifiche a queste impostazioni valgono solo per gli attestati emessi successivamente.';
$string['pluginname'] = 'Registro attestati';
$string['prefix'] = 'Prefisso';
$string['prefix_desc'] = 'Da 1 a 10 tra lettere e cifre, con almeno una lettera (es. CERT); viene salvato in maiuscolo. Cambiare il prefisso avvia una nuova serie di numeri: i numeri già assegnati restano validi.';
$string['privacy:metadata:certcode'] = 'Il codice stampato sull\'attestato.';
$string['privacy:metadata:certname'] = 'Il nome dell\'attestato.';
$string['privacy:metadata:certnumber'] = 'Il numero dell\'attestato.';
$string['privacy:metadata:cmid'] = 'L\'attività (modulo del corso) che ha rilasciato l\'attestato.';
$string['privacy:metadata:cohorts'] = 'Le coorti a cui apparteneva il corsista al momento del rilascio.';
$string['privacy:metadata:core_role'] = 'Un attestato annullato può essere bloccato: il corsista riceve nell\'attività dell\'attestato un ruolo che impedisce di rilasciarlo di nuovo.';
$string['privacy:metadata:courseid'] = 'Il corso dell\'attestato.';
$string['privacy:metadata:coursename'] = 'Il nome del corso al momento del rilascio.';
$string['privacy:metadata:customcertid'] = 'L\'attività Certificato personalizzato che ha rilasciato l\'attestato.';
$string['privacy:metadata:email'] = 'L\'indirizzo email del corsista al momento del rilascio.';
$string['privacy:metadata:firstname'] = 'Il nome del corsista al momento del rilascio.';
$string['privacy:metadata:issueid'] = 'L\'identificativo del rilascio in Certificato personalizzato.';
$string['privacy:metadata:lastname'] = 'Il cognome del corsista al momento del rilascio.';
$string['privacy:metadata:origcode'] = 'Il codice casuale generato da Certificato personalizzato prima della numerazione.';
$string['privacy:metadata:status'] = 'Se l\'attestato è valido, annullato o rimosso dalla piattaforma.';
$string['privacy:metadata:statusby'] = 'L\'utente che ha annullato o rimosso l\'attestato.';
$string['privacy:metadata:statusreason'] = 'Il motivo dell\'annullamento.';
$string['privacy:metadata:table'] = 'Il registro degli attestati numerati, con una copia dei dati del corsista e del corso presa al momento del rilascio di ogni attestato. Per impostazione predefinita viene conservato integro come documento di tenuta; l\'anonimizzazione su richiesta di cancellazione si attiva nelle impostazioni del plugin.';
$string['privacy:metadata:timeblocked'] = 'Quando al corsista è stato impedito di ricevere di nuovo l\'attestato annullato.';
$string['privacy:metadata:timeissued'] = 'La data di rilascio dell\'attestato.';
$string['privacy:metadata:timestatus'] = 'La data di annullamento o di rimozione dell\'attestato.';
$string['privacy:metadata:userid'] = 'Il corsista che ha ricevuto l\'attestato.';
$string['privacyanonymise'] = 'Anonimizza su richiesta di cancellazione';
$string['privacyanonymise_desc'] = 'Se attivo, una richiesta di cancellazione dei dati personali approvata (Privacy e politiche) anonimizza nome, cognome, email e coorti nel registro, lasciando numero, corso, date e stato. Se disattivo (predefinito) il registro resta integro come documento di tenuta: va indicato nell\'informativa e nel registro dei trattamenti.';
$string['privacyheading'] = 'Privacy';
$string['register'] = 'Registro attestati';
$string['registerempty'] = 'Nessun attestato ancora registrato. I numeri vengono assegnati al rilascio degli attestati.';
$string['registerintro'] = 'Ogni attestato riceve un numero come {$a->number}; il codice stampato sull\'attestato è {$a->code}, dove i caratteri finali impediscono di risalire ad altri attestati provando numeri consecutivi. Numerazione attiva dal {$a->since}.';
$string['reissuewarning'] = 'Questa attività emette gli attestati in automatico (emissione automatica o invio agli studenti): senza il blocco il corsista riceverebbe un nuovo attestato entro un minuto.';
$string['resetfilters'] = 'Azzera';
$string['searchplaceholder'] = 'Numero, codice, nome o email';
$string['settings'] = 'Impostazioni del registro attestati';
$string['status'] = 'Stato';
$string['status_annulled'] = 'Annullato';
$string['status_removed'] = 'Rimosso dalla piattaforma';
$string['status_valid'] = 'Valido';
$string['statusannulledinfo'] = 'Annullato da {$a->by} il {$a->date}: {$a->reason}';
$string['statusremovedbyinfo'] = 'Rimosso da {$a->by} il {$a->date}';
$string['statusremovedinfo'] = 'Rimosso il {$a->date}';
$string['suffixlength'] = 'Caratteri di controllo';
$string['suffixlength_desc'] = 'Caratteri casuali aggiunti in coda al codice stampato sull\'attestato (es. CERT-2026-000001-K7QXM). Impediscono di risalire agli attestati di altre persone provando numeri consecutivi nella pagina pubblica di verifica. Consigliato: 5.';
$string['suffixnone'] = 'Nessuno (sconsigliato)';
$string['summaryannulled'] = 'Annullati: {$a}';
$string['summaryremoved'] = 'Rimossi: {$a}';
$string['summarytotal'] = 'Totale: {$a}';
$string['summaryvalid'] = 'Validi: {$a}';
$string['taskreconcile'] = 'Riallineamento del registro attestati';
$string['timeissued'] = 'Data di rilascio';
$string['unblock'] = 'Togli il blocco';
$string['unblocked'] = 'Blocco tolto per l\'attestato {$a}.';
$string['warninglasterrors'] = 'L\'ultimo riallineamento ({$a}) ha segnalato dei problemi:';
$string['warningunregistered'] = 'Attestati emessi in attesa di numero: {$a}. Il riallineamento automatico li numera entro 10 minuti; se l\'avviso persiste, controllare il log del task "Riallineamento del registro attestati".';
$string['yearlyreset'] = 'Ripartenza annuale';
$string['yearlyreset_desc'] = 'Se attivo, l\'anno fa parte del numero (es. CERT-2026-000001) e la numerazione riparte da 1 ogni anno. Cambiarlo a numerazione avviata apre una nuova serie: i numeri già assegnati non cambiano.';
