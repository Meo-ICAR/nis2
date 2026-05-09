# Documento dei Requisiti

## Introduzione

Il **OpenID App Portal** è un portale di accesso centralizzato (app launcher) costruito con Laravel 13 e Filament. Funge da punto di ingresso unico per gli utenti dell'organizzazione: si autenticano tramite un Identity Provider OpenID Connect esterno e visualizzano le applicazioni a cui sono autorizzati ad accedere, con i relativi link di avvio.

Il portale non gestisce le identità direttamente: delega l'autenticazione all'IdP OpenID Connect e si occupa esclusivamente di autorizzazione (quali app può vedere/usare ogni utente) e presentazione (la griglia di app launcher).

Il pannello di amministrazione Filament consente agli amministratori di gestire applicazioni, ruoli, permessi e utenti.

---

## Glossario

- **Portal**: L'applicazione Laravel 13 oggetto di questo documento.
- **User**: Un utente finale autenticato tramite OpenID Connect.
- **Admin**: Un utente con ruolo amministrativo che accede al pannello Filament.
- **IdP** (Identity Provider): Il server OpenID Connect esterno che autentica gli utenti (es. Keycloak, Auth0, Azure AD).
- **Application**: Una voce nel portale che rappresenta un'applicazione esterna accessibile tramite URL.
- **Permission**: Un'associazione tra un User (o Role) e una o più Application, che determina la visibilità dell'applicazione nel portale.
- **Role**: Un gruppo logico di permessi assegnabile a uno o più User.
- **OIDC**: OpenID Connect, protocollo di autenticazione basato su OAuth 2.0.
- **Access Token**: Token JWT rilasciato dall'IdP al termine del flusso OIDC.
- **Session**: La sessione Laravel attiva per un User autenticato.
- **Launcher**: La pagina principale del portale che mostra la griglia delle applicazioni accessibili.
- **Filament**: Framework PHP per la costruzione di pannelli di amministrazione su Laravel.
- **Socialite**: Libreria Laravel per l'autenticazione OAuth/OIDC (già presente nel progetto).

---

## Requisiti

### Requisito 1: Autenticazione tramite OpenID Connect

**User Story:** Come utente, voglio accedere al portale usando il mio account aziendale tramite OpenID Connect, così da non dover gestire credenziali separate.

#### Criteri di Accettazione

1. WHEN un utente non autenticato accede a qualsiasi rotta protetta del Portal, THE Portal SHALL reindirizzare l'utente al flusso di autorizzazione OIDC dell'IdP.
2. WHEN l'IdP restituisce un authorization code valido al Portal, THE Portal SHALL scambiare il codice con un Access Token e recuperare il profilo utente (claims: `sub`, `email`, `name`).
3. WHEN il profilo utente viene ricevuto dall'IdP, THE Portal SHALL creare o aggiornare il record User corrispondente nel database locale usando il campo `sub` come identificatore univoco.
4. WHEN l'autenticazione OIDC ha successo, THE Portal SHALL creare una Session autenticata e reindirizzare l'utente al Launcher.
5. IF l'IdP restituisce un errore durante il flusso OIDC, THEN THE Portal SHALL mostrare una pagina di errore con un messaggio descrittivo e un link per riprovare.
6. IF il token ricevuto dall'IdP non è valido o è scaduto, THEN THE Portal SHALL invalidare la Session e reindirizzare l'utente alla pagina di login.
7. WHEN un utente autenticato richiede il logout, THE Portal SHALL invalidare la Session locale e reindirizzare l'utente all'endpoint di logout dell'IdP (RP-Initiated Logout).
8. THE Portal SHALL supportare la configurazione dell'IdP tramite variabili d'ambiente (`OIDC_CLIENT_ID`, `OIDC_CLIENT_SECRET`, `OIDC_REDIRECT_URI`, `OIDC_BASE_URL`).

---

### Requisito 2: Visualizzazione delle Applicazioni nel Launcher

**User Story:** Come utente autenticato, voglio vedere una griglia con tutte le applicazioni a cui ho accesso, così da poter avviare rapidamente qualsiasi applicazione autorizzata.

#### Criteri di Accettazione

1. WHEN un User autenticato accede al Launcher, THE Portal SHALL mostrare esclusivamente le Application per cui il User possiede un Permission attivo.
2. THE Portal SHALL visualizzare ogni Application con: nome, descrizione breve, icona/logo, e URL di destinazione.
3. WHEN un User clicca su una Application nel Launcher, THE Portal SHALL aprire l'URL della Application in una nuova scheda del browser.
4. WHILE un User non possiede Permission per nessuna Application, THE Portal SHALL mostrare un messaggio informativo che indica l'assenza di applicazioni disponibili.
5. THE Portal SHALL ordinare le Application nel Launcher in base all'ordine di visualizzazione (`sort_order`) definito dall'Admin.
6. WHERE la funzionalità di ricerca è abilitata, THE Portal SHALL filtrare le Application visibili in tempo reale in base al testo inserito dall'utente nel campo di ricerca, confrontando il testo con il nome e la descrizione della Application.
7. THE Portal SHALL aggiornare la lista delle Application visibili nel Launcher senza richiedere un nuovo login quando i Permission del User vengono modificati dall'Admin.

---

### Requisito 3: Gestione delle Applicazioni (Admin)

**User Story:** Come amministratore, voglio gestire il catalogo delle applicazioni disponibili nel portale, così da poter aggiungere, modificare o rimuovere applicazioni senza modificare il codice.

#### Criteri di Accettazione

1. THE Admin SHALL accedere alla gestione delle Application esclusivamente tramite il pannello Filament, protetto da autenticazione e verifica del ruolo amministrativo.
2. WHEN un Admin crea una nuova Application, THE Portal SHALL richiedere e validare i campi: nome (stringa, max 100 caratteri), URL (URL valido, max 500 caratteri), e stato attivo/inattivo.
3. WHEN un Admin crea una nuova Application, THE Portal SHALL accettare opzionalmente: descrizione (testo, max 500 caratteri), icona/logo (file immagine o URL), e valore `sort_order` (intero, default 0).
4. WHEN un Admin modifica una Application esistente, THE Portal SHALL aggiornare i dati nel database e riflettere le modifiche nel Launcher alla successiva visualizzazione.
5. WHEN un Admin disattiva una Application, THE Portal SHALL nascondere la Application dal Launcher per tutti gli User, indipendentemente dai Permission esistenti.
6. WHEN un Admin elimina una Application, THE Portal SHALL rimuovere anche tutti i Permission associati alla Application eliminata.
7. THE Portal SHALL mostrare nel pannello Filament una lista paginata delle Application con colonne: nome, URL, stato, numero di utenti con accesso, e data di creazione.

---

### Requisito 4: Gestione dei Permessi e dei Ruoli (Admin)

**User Story:** Come amministratore, voglio assegnare permessi di accesso alle applicazioni a singoli utenti o a gruppi (ruoli), così da controllare centralmente chi può accedere a cosa.

#### Criteri di Accettazione

1. THE Admin SHALL poter assegnare un Permission a un singolo User per una specifica Application tramite il pannello Filament.
2. THE Admin SHALL poter creare Role con un nome e una descrizione, e assegnare a ogni Role un insieme di Application accessibili.
3. WHEN un Admin assegna un Role a un User, THE Portal SHALL concedere al User l'accesso a tutte le Application associate al Role.
4. WHEN un User possiede sia Permission diretti che Permission derivati da Role, THE Portal SHALL mostrare nel Launcher l'unione di tutte le Application autorizzate, senza duplicati.
5. WHEN un Admin revoca un Permission diretto a un User per una Application, THE Portal SHALL rimuovere l'accesso a quella Application solo se il User non possiede anche un Role che include la stessa Application.
6. THE Portal SHALL mostrare nel pannello Filament, per ogni User, la lista completa delle Application accessibili con indicazione della fonte del permesso (diretto o tramite Role).
7. IF un Admin tenta di assegnare un Permission duplicato (stesso User e stessa Application già presenti), THEN THE Portal SHALL ignorare l'operazione senza generare un errore.

---

### Requisito 5: Sincronizzazione dei Ruoli dall'IdP

**User Story:** Come amministratore, voglio che i ruoli assegnati nell'IdP vengano sincronizzati automaticamente nel portale, così da non dover duplicare la gestione dei permessi.

#### Criteri di Accettazione

1. WHERE la sincronizzazione dei ruoli IdP è abilitata (`OIDC_SYNC_ROLES=true`), WHEN un User completa il flusso OIDC, THE Portal SHALL leggere il claim `roles` (o il claim configurato tramite `OIDC_ROLES_CLAIM`) dall'Access Token.
2. WHERE la sincronizzazione dei ruoli IdP è abilitata, WHEN il claim dei ruoli è presente nel token, THE Portal SHALL associare al User i Role del Portal il cui nome corrisponde ai valori del claim.
3. WHERE la sincronizzazione dei ruoli IdP è abilitata, WHEN il claim dei ruoli è presente nel token, THE Portal SHALL rimuovere dal User i Role del Portal che non sono presenti nel claim, preservando i Permission diretti.
4. WHERE la sincronizzazione dei ruoli IdP è disabilitata, THE Portal SHALL ignorare il claim dei ruoli nel token e mantenere invariati i Role assegnati manualmente.
5. IF il claim dei ruoli nell'Access Token contiene un valore che non corrisponde a nessun Role esistente nel Portal, THEN THE Portal SHALL ignorare quel valore senza generare un errore.

---

### Requisito 6: Gestione degli Utenti (Admin)

**User Story:** Come amministratore, voglio visualizzare e gestire gli utenti che hanno effettuato l'accesso al portale, così da avere visibilità completa sulla base utenti.

#### Criteri di Accettazione

1. THE Portal SHALL creare automaticamente un record User nel database al primo accesso OIDC riuscito, popolando i campi `sub`, `email`, `name` dai claims del token.
2. THE Portal SHALL aggiornare i campi `email` e `name` del record User ad ogni accesso OIDC riuscito, se i valori nel token differiscono da quelli nel database.
3. THE Admin SHALL poter visualizzare nel pannello Filament la lista degli User con colonne: nome, email, data primo accesso, data ultimo accesso, numero di applicazioni accessibili.
4. THE Admin SHALL poter assegnare o revocare il ruolo amministrativo a qualsiasi User tramite il pannello Filament.
5. WHEN un Admin disabilita un User, THE Portal SHALL impedire al User di accedere al Launcher e alle applicazioni, anche se il flusso OIDC ha successo.
6. IF un User disabilitato completa il flusso OIDC, THEN THE Portal SHALL invalidare la Session e mostrare un messaggio che informa l'utente che il suo account è disabilitato.

---

### Requisito 7: Sicurezza e Protezione delle Rotte

**User Story:** Come responsabile della sicurezza, voglio che tutte le rotte del portale siano adeguatamente protette, così da garantire che solo gli utenti autenticati e autorizzati possano accedere alle risorse.

#### Criteri di Accettazione

1. THE Portal SHALL proteggere tutte le rotte del Launcher con il middleware di autenticazione, reindirizzando gli utenti non autenticati al flusso OIDC.
2. THE Portal SHALL proteggere tutte le rotte del pannello Filament con verifica del ruolo amministrativo, restituendo HTTP 403 agli utenti autenticati privi di tale ruolo.
3. THE Portal SHALL implementare la protezione CSRF su tutti i form e le richieste POST/PUT/DELETE.
4. THE Portal SHALL memorizzare il token OIDC esclusivamente nella Session server-side, senza esporlo al client.
5. WHEN la Session di un User scade, THE Portal SHALL reindirizzare l'utente al flusso OIDC per una nuova autenticazione.
6. THE Portal SHALL validare il parametro `state` durante il callback OIDC per prevenire attacchi CSRF sul flusso di autenticazione.
7. IF una Application ha un URL che non rispetta il protocollo HTTPS, THEN THE Portal SHALL mostrare un avviso visivo nel pannello Filament durante la creazione o modifica della Application.

---

### Requisito 8: Audit Log

**User Story:** Come amministratore, voglio che le azioni rilevanti nel portale vengano registrate in un log di audit, così da poter tracciare accessi e modifiche per motivi di sicurezza e conformità.

#### Criteri di Accettazione

1. WHEN un User completa un accesso OIDC con successo, THE Portal SHALL registrare un evento di audit con: tipo evento (`login`), `user_id`, indirizzo IP, timestamp.
2. WHEN un User effettua il logout, THE Portal SHALL registrare un evento di audit con: tipo evento (`logout`), `user_id`, timestamp.
3. WHEN un Admin crea, modifica o elimina una Application, THE Portal SHALL registrare un evento di audit con: tipo evento, `admin_id`, dati modificati (before/after), timestamp.
4. WHEN un Admin assegna o revoca un Permission o un Role, THE Portal SHALL registrare un evento di audit con: tipo evento, `admin_id`, `user_id` o `role_id` coinvolto, `application_id`, timestamp.
5. THE Admin SHALL poter visualizzare il log di audit nel pannello Filament con filtri per: tipo evento, utente, intervallo di date.
6. THE Portal SHALL conservare i record di audit log per un periodo minimo di 90 giorni.

---

## Prompt per Vibe Coding

Questa sezione raccoglie prompt pronti all'uso da fornire a un AI assistant per implementare le singole funzionalità del portale.

---

### Prompt 1 — Setup OIDC con Laravel Socialite

```
Sto costruendo un portale Laravel 13 con autenticazione OpenID Connect.
Il progetto ha già installato `laravel/socialite` e `socialiteproviders/manager`.

Implementa il flusso di autenticazione OIDC completo:
1. Configura un provider Socialite generico OIDC in `config/services.php` che legge le variabili d'ambiente: OIDC_CLIENT_ID, OIDC_CLIENT_SECRET, OIDC_REDIRECT_URI, OIDC_BASE_URL.
2. Crea un `AuthController` con i metodi `redirect()` (avvia il flusso OIDC) e `callback()` (gestisce il ritorno dall'IdP).
3. Nel metodo `callback()`: recupera il profilo utente da Socialite, crea o aggiorna il record User nel DB usando il campo `sub` come identificatore univoco, crea la sessione autenticata, reindirizza al Launcher.
4. Gestisci il caso di errore: se Socialite lancia un'eccezione, mostra una pagina di errore con messaggio descrittivo.
5. Implementa il logout: invalida la sessione locale e reindirizza all'endpoint di logout dell'IdP (RP-Initiated Logout) usando il parametro `post_logout_redirect_uri`.
6. Aggiungi le rotte in `routes/web.php`: GET /auth/redirect, GET /auth/callback, POST /auth/logout.
7. Proteggi tutte le rotte del Launcher con un middleware `auth` che reindirizza al flusso OIDC.

Il modello User ha già i campi standard Laravel. Aggiungi una migration per i campi: `sub` (string, unique), `oidc_token` (text, nullable), `last_login_at` (timestamp, nullable), `is_active` (boolean, default true).
```

---

### Prompt 2 — Modelli e Migration per Applicazioni e Permessi

```
In un progetto Laravel 13, crea i modelli Eloquent e le migration per il sistema di permessi del portale app launcher.

Schema richiesto:

Tabella `applications`:
- id (ulid o bigint)
- name (string, 100)
- description (text, nullable)
- url (string, 500)
- icon_url (string, nullable)
- sort_order (integer, default 0)
- is_active (boolean, default true)
- timestamps

Tabella `roles`:
- id
- name (string, unique)
- description (text, nullable)
- timestamps

Tabella `role_application` (pivot):
- role_id, application_id

Tabella `user_role` (pivot):
- user_id, role_id
- source (enum: 'manual', 'oidc') — indica se il ruolo è stato assegnato manualmente o sincronizzato dall'IdP

Tabella `user_application` (pivot, permessi diretti):
- user_id, application_id

Tabella `audit_logs`:
- id
- event_type (string)
- user_id (nullable, foreign)
- admin_id (nullable, foreign)
- subject_type (string, nullable) — es. 'Application', 'Role'
- subject_id (nullable)
- payload (json, nullable) — dati before/after
- ip_address (string, nullable)
- created_at

Crea i modelli con le relazioni Eloquent corrette:
- User hasMany roles (tramite pivot), hasMany directApplications (tramite pivot)
- User: metodo `accessibleApplications()` che restituisce l'unione di applicazioni dirette + applicazioni dei ruoli, senza duplicati, ordinate per sort_order
- Application belongsToMany roles, belongsToMany users (diretti)
- Role belongsToMany applications, belongsToMany users

Aggiungi scope `active()` al modello Application.
```

---

### Prompt 3 — Sincronizzazione Ruoli dall'IdP

```
In un progetto Laravel 13, implementa la sincronizzazione dei ruoli OIDC nel portale app launcher.

Contesto: dopo il login OIDC, l'Access Token può contenere un claim con i ruoli dell'utente (nome del claim configurabile tramite env `OIDC_ROLES_CLAIM`, default: `roles`). La sincronizzazione è abilitata tramite `OIDC_SYNC_ROLES=true`.

Implementa:
1. Un servizio `OidcRoleSyncService` con metodo `sync(User $user, array $tokenClaims): void` che:
   - Legge il claim dei ruoli dal token
   - Trova i Role nel DB il cui `name` corrisponde ai valori del claim
   - Assegna i Role trovati all'utente con `source = 'oidc'` nella pivot
   - Rimuove dall'utente i Role con `source = 'oidc'` che non sono più nel claim
   - Non tocca i Role con `source = 'manual'`
   - Ignora silenziosamente i valori del claim che non corrispondono a nessun Role nel DB
2. Integra il servizio nel `AuthController::callback()` dopo la creazione/aggiornamento dell'utente.
3. Aggiungi i test unitari per `OidcRoleSyncService` coprendo i casi: sincronizzazione normale, rimozione ruoli non più presenti, preservazione ruoli manuali, claim assente, claim con valori non esistenti nel DB.
```

---

### Prompt 4 — Pannello Filament: Gestione Applicazioni

```
In un progetto Laravel 13 con Filament v3, crea la risorsa Filament per la gestione delle Application nel pannello admin del portale app launcher.

Crea `App\Filament\Resources\ApplicationResource` con:

Form fields:
- name: TextInput, required, max 100
- url: TextInput (URL), required, max 500, con avviso visivo se il protocollo non è HTTPS
- description: Textarea, nullable, max 500
- icon_url: TextInput (URL) o FileUpload (immagine), nullable
- sort_order: TextInput numerico, default 0
- is_active: Toggle, default true

Table columns:
- name
- url (truncata, cliccabile)
- is_active (badge verde/rosso)
- Colonna calcolata: numero di utenti con accesso (diretti + tramite ruolo)
- created_at

Table actions:
- Edit, Delete (con conferma)
- Bulk action: Attiva/Disattiva selezionati

Filters:
- is_active

Implementa l'observer `ApplicationObserver` che:
- Al delete: rimuove tutti i Permission diretti e le associazioni Role-Application
- Registra l'evento nel log di audit (tabella audit_logs)

Proteggi la risorsa in modo che sia accessibile solo agli utenti con ruolo amministrativo.
```

---

### Prompt 5 — Pannello Filament: Gestione Utenti e Permessi

```
In un progetto Laravel 13 con Filament v3, crea la risorsa Filament per la gestione degli User e dei loro permessi nel portale app launcher.

Crea `App\Filament\Resources\UserResource` con:

Form fields:
- name, email: sola lettura (sincronizzati dall'IdP)
- is_active: Toggle
- is_admin: Toggle (assegna/revoca ruolo amministrativo)
- Sezione "Permessi diretti": CheckboxList delle Application attive
- Sezione "Ruoli": CheckboxList dei Role disponibili, con badge che indica se il ruolo è stato assegnato manualmente o sincronizzato dall'IdP

Table columns:
- name, email
- is_active (badge)
- is_admin (badge)
- Numero applicazioni accessibili (calcolato)
- last_login_at
- created_at

RelationManager `AccessibleApplicationsRelationManager`:
- Mostra la lista completa delle Application accessibili all'utente
- Per ogni Application indica la fonte del permesso: "Diretto" o "Tramite ruolo: {nome_ruolo}"

Implementa la logica di revoca permesso: quando si rimuove un permesso diretto, verificare se l'utente ha ancora accesso tramite un Role e mostrare un avviso informativo nel form.

Registra tutte le modifiche ai permessi nel log di audit.
```

---

### Prompt 6 — Launcher Page (Frontend Filament)

```
In un progetto Laravel 13 con Filament v3, crea la pagina Launcher del portale app launcher.

La pagina è la home page del portale (rotta `/`), accessibile a tutti gli utenti autenticati (non solo admin).

Crea `App\Filament\Pages\Launcher` (Filament Page) con:

Layout:
- Griglia responsive di card (3 colonne su desktop, 2 su tablet, 1 su mobile)
- Ogni card mostra: icona/logo dell'applicazione, nome, descrizione breve
- Click sulla card apre l'URL in una nuova scheda
- Se l'utente non ha applicazioni: messaggio "Nessuna applicazione disponibile. Contatta l'amministratore."

Funzionalità:
- Campo di ricerca in tempo reale (filtra per nome e descrizione usando Livewire)
- Le applicazioni sono ordinate per `sort_order`
- Mostra solo le Application `is_active = true` per cui l'utente ha un Permission (diretto o tramite Role)

Usa il metodo `User::accessibleApplications()` per recuperare le applicazioni.

Stile: usa i componenti Filament nativi (Card, Grid) e Tailwind CSS. Le card devono avere hover effect e transizione CSS.

La pagina NON deve essere nel menu di navigazione del pannello admin (è la home pubblica del portale).
```

---

### Prompt 7 — Audit Log nel Pannello Filament

```
In un progetto Laravel 13 con Filament v3, crea la pagina di visualizzazione dell'Audit Log nel pannello admin del portale app launcher.

Crea `App\Filament\Resources\AuditLogResource` (read-only, nessun form di creazione/modifica) con:

Table columns:
- event_type (badge colorato per tipo: login=blu, logout=grigio, create=verde, update=giallo, delete=rosso, permission_change=viola)
- user: nome dell'utente coinvolto (nullable)
- admin: nome dell'admin che ha eseguito l'azione (nullable)
- subject: tipo e ID del soggetto (es. "Application #5")
- ip_address
- created_at (con formato relativo: "2 ore fa")

Filters:
- event_type (select multiplo)
- Intervallo di date (DateRangePicker)
- user_id

Actions:
- View (modal) che mostra il payload JSON formattato (before/after per le modifiche)

Implementa la pulizia automatica: crea un comando Artisan `audit:prune` che elimina i record più vecchi di 90 giorni. Registra il comando nello scheduler Laravel (esecuzione giornaliera).

Proteggi la risorsa in modo che sia accessibile solo agli utenti con ruolo amministrativo.
```

---

### Prompt 8 — Test di Integrazione e Feature Tests

```
In un progetto Laravel 13 con PHPUnit, scrivi i test per il portale app launcher.

Scrivi i seguenti test:

Feature Tests (`tests/Feature/`):

1. `OidcAuthTest`:
   - test_unauthenticated_user_is_redirected_to_oidc: verifica che GET / reindirizza al flusso OIDC
   - test_callback_creates_user_on_first_login: mocka Socialite, verifica creazione User nel DB
   - test_callback_updates_user_on_subsequent_login: verifica aggiornamento email/name
   - test_disabled_user_cannot_login: verifica che utente disabilitato riceva errore
   - test_logout_invalidates_session: verifica invalidazione sessione

2. `LauncherTest`:
   - test_user_sees_only_permitted_applications: verifica che il Launcher mostri solo le app autorizzate
   - test_user_with_no_permissions_sees_empty_state: verifica messaggio vuoto
   - test_inactive_application_is_hidden: verifica che app disattivate non appaiano
   - test_search_filters_applications: verifica il filtro di ricerca

3. `PermissionTest`:
   - test_direct_permission_grants_access
   - test_role_permission_grants_access
   - test_union_of_direct_and_role_permissions_has_no_duplicates
   - test_revoking_direct_permission_preserves_role_access

Unit Tests (`tests/Unit/`):

4. `OidcRoleSyncServiceTest`:
   - test_sync_assigns_matching_roles
   - test_sync_removes_oidc_roles_not_in_claim
   - test_sync_preserves_manual_roles
   - test_sync_ignores_unknown_role_names
   - test_sync_does_nothing_when_disabled

Usa factory e database transactions per isolare i test.
Mocka Socialite usando `Socialite::shouldReceive()`.
```
