# Registro attestati (local_certledger)

Plugin Moodle per numerare e registrare gli attestati di Certificato personalizzato.

Assegna a ogni attestato emesso dall'attività **Certificato personalizzato** (mod_customcert) un **numero progressivo**,
lo **stampa sull'attestato** e tiene un **registro** consultabile ed esportabile dagli amministratori.

## Come funziona

1. Quando Certificato personalizzato emette un attestato, il plugin riceve l'evento `issue_created` e assegna subito
   il numero successivo della serie, ad esempio `CERT-2026-000001`.
   - Un blocco (lock) del database garantisce che due emissioni contemporanee non ricevano lo stesso numero.
   - Indici univoci sul registro fanno da seconda garanzia.
2. Il codice dell'attestato viene sostituito con il numero più alcuni caratteri casuali di controllo, ad esempio
   `CERT-2026-000001-K7QXM`. Certificato personalizzato lo stampa con i suoi elementi standard **Codice** e **Codice QR**
   e lo verifica con la sua pagina di verifica.
   - I caratteri di controllo impediscono di trovare gli attestati di altre persone provando numeri consecutivi.
3. Nel registro viene salvata una **fotografia** dei dati al momento del rilascio: corsista, email, corso, attestato,
   coorti di appartenenza, data. Il registro resta valido anche se poi utenti o corsi vengono modificati
   o eliminati.

Certificato personalizzato **non viene modificato**: il plugin usa solo i suoi eventi pubblici e la sua tabella
`customcert_issues`. Si può aggiornare Certificato personalizzato senza toccare questo plugin.

## Stati di un attestato

| Stato | Quando |
|---|---|
| **Valido** | L'attestato esiste sulla piattaforma ed è scaricabile e verificabile. |
| **Annullato** | Annullato dal registro, con motivazione. L'attestato viene eliminato dalla piattaforma. |
| **Rimosso dalla piattaforma** | Eliminato in altro modo: dal report dell'attività, eliminando l'attività o il corso, con l'azzeramento del corso. |

Un numero non viene **mai** riassegnato: gli attestati annullati o rimossi restano nel registro. Prima di assegnare
un numero il plugin controlla anche i codici già presenti sugli attestati emessi, così un numero stampato non viene
riusato nemmeno se il registro è stato perso o ripristinato da un backup più vecchio.

### Annullamento e blocco

Annullare un attestato lo elimina dalla piattaforma, **comprese le copie** create da un ripristino del corso.
Certificato personalizzato però emette un nuovo attestato (con un nuovo numero) appena il corsista riapre l'attività,
o entro un minuto se l'emissione è automatica o è attivo l'invio agli studenti. Per questo la pagina di annullamento
propone, attivo per impostazione predefinita, il **blocco**: un ruolo tecnico ("Attestato bloccato") assegnato al
corsista nell'attività, che toglie il permesso di ricevere l'attestato. Il blocco si toglie dal registro
(pulsante "Togli il blocco" sulla riga annullata). Il ruolo viene creato automaticamente al primo uso; il blocco è
registrato anche nel registro, così se Moodle rimuove l'assegnazione del ruolo (ad esempio disiscrivendo e reiscrivendo
il corsista) il riallineamento la ripristina. La disinstallazione del plugin elimina ruolo e assegnazioni.

## Dove si trova

- **Registro**: Amministrazione del sito → Report → Registro attestati (`/local/certledger/index.php`).
  - Filtri per anno, corso, stato e ricerca per numero, codice, nome o email.
  - Esportazione in tutti i formati abilitati (Excel, CSV, ODS, PDF…).
- **Impostazioni**: Amministrazione del sito → Plugin → Plugin locali → Registro attestati.
  - Prefisso, ripartenza annuale, cifre, caratteri di controllo.
  - Le modifiche valgono solo per gli attestati emessi dopo.

## Permessi

| Capability | Default | Cosa permette |
|---|---|---|
| `local/certledger:view` | Manager | Consultare ed esportare il registro (contiene dati personali) |
| `local/certledger:annul` | Manager | Annullare un attestato |

Gli amministratori del sito hanno sempre entrambi i permessi.

Consultazioni, esportazioni, annullamenti e numeri assegnati vengono registrati nei log di Moodle.

## Configurare un attestato

1. Nel modello dell'attestato aggiungere l'elemento **Codice**, che stampa il numero, e se si vuole **Codice QR**.
2. Per il rilascio automatico al completamento, nell'attività Certificato personalizzato:
   - aggiungere una **condizione di accesso** sul completamento delle attività del corso (o del corso stesso);
   - attivare l'invio dell'attestato via email agli studenti oppure, da Certificato personalizzato 5.0.8 in poi,
     l'opzione di emissione automatica nelle impostazioni dell'attività.
   Senza queste opzioni l'attestato viene emesso, e numerato, quando il corsista lo scarica dall'attività.

## Riallineamento automatico

Il task pianificato **Riallineamento del registro attestati** gira ogni 10 minuti e, nell'ordine:

1. **Ricollega** un attestato valido o rimosso il cui record di Certificato personalizzato era sparito e che è
   ricomparso con lo stesso codice (attività ripristinata dal cestino o da un backup): torna "Valido" con i nuovi
   riferimenti, conservando numero, data e fotografia dei dati.
2. **Elimina le copie** di attestati annullati (stesso codice), così un attestato annullato non torna mai valido.
3. **Reinserisce con il loro numero** gli attestati che portano già un numero del plugin ma mancano dal registro
   (plugin disinstallato e reinstallato, tabella ripristinata da un backup più vecchio): il codice stampato non cambia mai.
4. **Numera** gli attestati che per qualsiasi motivo non hanno ricevuto il numero al momento dell'emissione.
5. **Segna come rimossi** quelli eliminati senza evento, ad esempio con l'azzeramento del corso o con una cancellazione
   per privacy.
6. **Rimette il numero** sull'attestato se qualcosa ha cambiato il codice.

Le **copie** di attestati validi, ad esempio da un ripristino del corso con i dati utente in un altro corso, **non**
vengono rinumerate né registrate: restano scaricabili con il codice originale e il task le conta ("copies left").

Gli errori del riallineamento vengono scritti nel log del task e mostrati nella pagina del registro fino alla prima
esecuzione senza errori; la pagina avvisa anche se ci sono attestati emessi in attesa di numero.

**Limite noto.** Se un componente esterno emette l'attestato dentro una transazione del database, la numerazione
avviene subito dopo il commit (sempre nella stessa richiesta); se invece il blocco di numerazione non è ottenibile
(caso raro), un PDF scaricato prima del riallineamento porta il codice casuale di Certificato personalizzato; il
riallineamento lo sostituisce con il numero entro 10 minuti e conserva il codice precedente nel registro
(`origcode`). Ricaricando l'attestato il corsista ottiene il PDF con il numero.

## Privacy (GDPR)

Il plugin implementa la Privacy API di Moodle:

- **Esportazione**: nell'esportazione dei dati personali di un utente compaiono i suoi attestati e gli annullamenti
  che ha eseguito.
- **Cancellazione**: per impostazione predefinita il registro **viene conservato integro** anche dopo una richiesta di
  cancellazione approvata, come documento di tenuta (la numerazione deve restare completa e verificabile): questa
  scelta va riportata nell'informativa privacy e nel registro dei trattamenti. Attivando l'impostazione
  "Anonimizza su richiesta di cancellazione", le richieste approvate anonimizzano nome, cognome, email, coorti e
  motivazioni, mantenendo numero, corso, date e stato.
  Attenzione: Moodle approva le richieste per tutti i plugin insieme; con il Data registry configurato il contesto
  di sistema, dove sta il registro, non viene incluso nelle cancellazioni (è il comportamento standard di Moodle).

## Riga di comando

Dalla cartella di Moodle (in Moodle 5.1 e successivi i file web stanno in `public/`):

```
# Elenca gli attestati non ancora numerati (senza modificare nulla)
php public/local/certledger/cli/backfill.php --all
# Li numera; quelli che hanno già un numero del plugin vengono reinseriti con quel numero
php public/local/certledger/cli/backfill.php --all --run
```

## Compatibilità e aggiornamenti

- **Requisiti**: Moodle 5.0 o successivo, Certificato personalizzato (mod_customcert) 5.0 o successivo.
- **Interfacce usate**: interfacce stabili di Moodle (eventi, lock, `table_sql`, Privacy API, task pianificati) e, di
  Certificato personalizzato, solo gli eventi pubblici e la tabella `customcert_issues` (colonna `code`), presenti
  anche nella versione 5.3.
- **Backup**: il registro non fa parte dei backup di corso; va incluso nel backup del database. Un ripristino
  completo del database da un backup più vecchio riporta indietro anche il registro: i numeri assegnati dopo il backup
  vengono reinseriti dal riallineamento a partire dagli attestati esistenti.
- **Verifica pubblica**: un attestato annullato non risulta più nella pagina di verifica di Certificato
  personalizzato, esattamente come un codice inesistente; la motivazione è consultabile solo nel registro.
- **Aggiornando Moodle** sostituendo la cartella del codice, ricordarsi di copiare anche
  `public/local/certledger` (come gli altri plugin aggiuntivi).
- **Disinstallazione**: prima di eliminare la tabella, il registro viene salvato in
  `moodledata/local_certledger/registro-<data>.csv`. I codici già scritti sugli attestati restano e una
  reinstallazione li reinserisce con i loro numeri.
