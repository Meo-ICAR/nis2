# Piano di Implementazione: OpenID App Portal

## Overview

Implementazione incrementale del portale app launcher con autenticazione OIDC, gestione permessi/ruoli, pannello Filament e audit log. Il **Prompt 1** (setup OIDC base con Laravel Socialite) è già stato implementato: le task partono dal Prompt 2.

Stack: Laravel 13, Filament v5, PHP 8.3, SQLite (dev), PHPUnit 12, Eris (PBT).

---

## Tasks

- [x] 1. Migration per estendere la tabella users con i campi OIDC
  - Creare migration `add_oidc_fields_to_users_table` che aggiunge: `sub` (string, unique, nullable), `last_login_at` (timestamp, nullable), `is_active` (boolean, default true), `is_admin` (boolean, default false)
  - Verificare che la migration sia idempotente (usa `hasColumn` o `whenTableDoesntHaveColumn`)
  - Aggiornare il modello `User`: aggiungere i nuovi campi al `$fillable` (o attributi PHP 8), aggiungere i cast (`is_active` → boolean, `is_admin` → boolean, `last_login_at` → datetime)
  - _Requirements: 1.3, 6.1, 6.5_

- [x] 2. Migration e modelli per applications, roles, pivot tables e audit_logs
  - [x] 2.1 Creare migration `create_applications_table` con tutti i campi inclusi quelli NIS2 (`scientific_owner`, `internal_technical_contact`, `external_technical_contact`, `external_technical_email`, `support_contract_expiry`, `contract_notes`)
    - _Requirements: 3.2, 3.3, 3.4_
  - [x] 2.2 Creare migration `create_roles_table` con `name` (unique) e `description`
    - _Requirements: 4.2_
  - [x] 2.3 Creare migration `create_role_application_table` (pivot con FK cascade)
    - _Requirements: 4.2, 3.7_
  - [x] 2.4 Creare migration `create_user_role_table` (pivot con colonna `source` enum `manual|oidc` e FK cascade)
    - _Requirements: 4.3, 5.2, 5.3_
  - [x] 2.5 Creare migration `create_user_application_table` (pivot permessi diretti con FK cascade)
    - _Requirements: 4.1, 3.7_
  - [x] 2.6 Creare migration `create_audit_logs_table` con indici su `event_type`, `user_id`, `created_at`; `user_id` e `admin_id` FK nullable con `ON DELETE SET NULL`
    - _Requirements: 8.1, 8.2, 8.3, 8.4_
  - [x] 2.7 Creare modello `App\Models\Application` con: `$fillable`, cast (`is_active` → boolean, `support_contract_expiry` → date), scope `scopeActive()`, scope `scopeExpiringWithin(int $days)`, scope `scopeExpired()`, accessor `contractStatus(): string|null` che restituisce `'expired'|'expiring'|'valid'|null`
    - _Requirements: 3.6, 3.9, 3.10, 3.11_
  - [x] 2.8 Creare modello `App\Models\Role` con `$fillable` e relazioni `belongsToMany(Application)` e `belongsToMany(User)` (con pivot `source`)
    - _Requirements: 4.2, 4.3_
  - [x] 2.9 Creare modello `App\Models\AuditLog` con `$fillable`, cast `payload` → array, e `$timestamps = false` (solo `created_at`)
    - _Requirements: 8.1, 8.2, 8.3, 8.4_
  - [x] 2.10 Scrivere property test per `contractStatus()` (Property 8)
    - **Property 8: Badge scadenza contratto riflette la data corrente**
    - **Validates: Requirements 3.9, 3.10**
    - Usare Eris con generatori di date arbitrarie; verificare che `contractStatus()` restituisca `'expired'`, `'expiring'`, `'valid'` o `null` in base alla data rispetto a `now()`

- [x] 3. Aggiornamento modello User con relazioni e metodo accessibleApplications()
  - Aggiungere al modello `User`: relazione `belongsToMany(Role)` con pivot `source`, relazione `belongsToMany(Application)` (diretti, tabella `user_application`)
  - Implementare `accessibleApplications(): Collection` che restituisce l'unione senza duplicati di applicazioni dirette + applicazioni dei ruoli, filtrate per `is_active = true`, ordinate per `sort_order`
  - Implementare `isAdmin(): bool` che legge il campo `is_admin`
  - _Requirements: 2.1, 4.3, 4.4, 4.5_
  - [x] 3.1 Scrivere property test per `accessibleApplications()` — unione e assenza duplicati (Property 2, 3)
    - **Property 2: Il Launcher mostra esattamente le applicazioni autorizzate**
    - **Property 3: Nessun duplicato nell'unione dei permessi**
    - **Validates: Requirements 2.1, 4.3, 4.4**
    - Usare Eris con generatori di interi per creare set arbitrari di permessi diretti e ruoli
  - [x] 3.2 Scrivere property test per ordinamento e filtro is_active (Property 4, 6)
    - **Property 4: Ordinamento per sort_order**
    - **Property 6: Applicazione disattivata non appare nel Launcher**
    - **Validates: Requirements 2.5, 3.6**
  - [x] 3.3 Scrivere property test per idempotenza assegnazione permesso e revoca con ruolo (Property 12, 13)
    - **Property 12: Revoca permesso diretto preserva l'accesso via ruolo**
    - **Property 13: Idempotenza dell'assegnazione permesso**
    - **Validates: Requirements 4.5, 4.7**

- [-] 4. Checkpoint — Eseguire le migration e verificare lo schema
  - Eseguire `php artisan migrate` e verificare che tutte le tabelle siano create correttamente
  - Verificare che i modelli siano istanziabili e le relazioni funzionino con dati di test minimali
  - Assicurarsi che tutti i test esistenti passino; chiedere all'utente se sorgono dubbi.

- [ ] 5. OidcRoleSyncService e integrazione nel callback OIDC
  - [ ] 5.1 Creare `App\Services\OidcRoleSyncService` con metodo `sync(User $user, array $tokenClaims): void`
    - Leggere `OIDC_SYNC_ROLES` (default false); se false, ritornare immediatamente
    - Leggere il claim da `OIDC_ROLES_CLAIM` (default `roles`) dai `$tokenClaims`
    - Trovare i `Role` nel DB il cui `name` è nel claim
    - Assegnare i ruoli trovati con `source = 'oidc'` usando `syncWithoutDetaching`
    - Rimuovere i ruoli con `source = 'oidc'` non più nel claim (senza toccare `source = 'manual'`)
    - Ignorare silenziosamente i valori del claim senza corrispondenza nel DB
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  - [ ] 5.2 Integrare `OidcRoleSyncService` nel callback OIDC di `filament-socialite` tramite `resolveUserUsing()` in `AdminPanelProvider` o `AppServiceProvider`
    - Chiamare `sync()` dopo la creazione/aggiornamento dell'utente
    - Aggiornare `last_login_at` ad ogni login riuscito
    - _Requirements: 5.1, 6.2_
  - [ ] 5.3 Scrivere unit test per `OidcRoleSyncService` (Property 14, 15, 16)
    - **Property 14: Sincronizzazione ruoli OIDC assegna i ruoli corrispondenti**
    - **Property 15: Sincronizzazione OIDC preserva i ruoli manuali**
    - **Property 16: Sincronizzazione OIDC ignora ruoli sconosciuti senza errori**
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.5**
    - Usare Eris con generatori di array di stringhe per i claim; verificare le tre proprietà con 100+ iterazioni

- [ ] 6. AuditLogService
  - Creare `App\Services\AuditLogService` con metodo `log(string $eventType, array $context = []): void`
  - Implementare metodi helper: `logLogin(User $user, string $ip): void`, `logLogout(User $user): void`, `logApplicationChange(string $eventType, User $admin, Application $app, array $before, array $after): void`, `logPermissionChange(string $eventType, User $admin, array $context): void`
  - Registrare il servizio nel container Laravel (binding in `AppServiceProvider`)
  - _Requirements: 8.1, 8.2, 8.3, 8.4_

- [ ] 7. ApplicationObserver
  - Creare `App\Observers\ApplicationObserver` con:
    - `deleting()`: rimuovere tutti i record in `user_application` e `role_application` per l'applicazione eliminata
    - `created()`: chiamare `AuditLogService::logApplicationChange('create', ...)`
    - `updated()`: chiamare `AuditLogService::logApplicationChange('update', ...)` con before/after
    - `deleted()`: chiamare `AuditLogService::logApplicationChange('delete', ...)`
  - Registrare l'observer in `AppServiceProvider::boot()`
  - _Requirements: 3.7, 8.3_
  - [ ] 7.1 Scrivere property test per eliminazione applicazione (Property 7)
    - **Property 7: Eliminazione applicazione rimuove tutti i permessi associati**
    - **Validates: Requirements 3.7**
    - Usare Eris con generatori di interi per creare N permessi diretti e M associazioni ruolo arbitrari prima del delete

- [ ] 8. Filament ApplicationResource
  - [ ] 8.1 Creare `App\Filament\Resources\ApplicationResource` con form diviso in due sezioni: "Generale" (name, url, description, icon_url, sort_order, is_active) e "Conformità NIS2" (tutti i campi NIS2)
    - Aggiungere avviso visivo (`Hint` o `Placeholder`) se l'URL non usa HTTPS
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 7.7_
  - [ ] 8.2 Implementare la tabella con colonne: name, url (troncata), is_active (badge), scientific_owner, internal_technical_contact, support_contract_expiry con badge colorato (verde/arancione "In scadenza"/rosso "Scaduto"), numero utenti con accesso, created_at
    - _Requirements: 3.8, 3.9, 3.10_
  - [ ] 8.3 Aggiungere filtri: is_active, "Contratto in scadenza" (select: Scaduto / Entro 30gg / Entro 60gg / Entro 90gg), scientific_owner (text search), internal_technical_contact (text search)
    - _Requirements: 3.11_
  - [ ] 8.4 Aggiungere bulk actions (Attiva/Disattiva selezionati) e action "Esporta CSV" con tutti i campi NIS2
    - _Requirements: 3.12_
  - [ ] 8.5 Proteggere la risorsa con `canAccess()` / policy che verifica `is_admin`
    - _Requirements: 3.1, 7.2_
  - [ ] 8.6 Scrivere property test per export CSV (Property 10)
    - **Property 10: Export CSV contiene tutti i campi NIS2**
    - **Validates: Requirements 3.12**
    - Usare Eris con generatori di stringhe per i campi NIS2; verificare che ogni riga del CSV contenga tutti i campi valorizzati correttamente
  - [ ] 8.7 Scrivere property test per filtro contratto in scadenza (Property 9)
    - **Property 9: Filtro contratto in scadenza è coerente con contractStatus**
    - **Validates: Requirements 3.11**

- [ ] 9. Filament RoleResource
  - Creare `App\Filament\Resources\RoleResource` con:
    - Form: `name` (TextInput, required, unique), `description` (Textarea), `CheckboxList` delle Application attive
    - Table: name, description, numero applicazioni associate, numero utenti con quel ruolo
    - Actions: Edit, Delete (con conferma)
  - Proteggere la risorsa con verifica `is_admin`
  - _Requirements: 4.2_

- [ ] 10. Filament UserResource con permessi diretti, ruoli e RelationManager
  - [ ] 10.1 Creare `App\Filament\Resources\UserResource` con form: name e email (sola lettura), `is_active` (Toggle), `is_admin` (Toggle), sezione "Permessi diretti" (CheckboxList Application attive), sezione "Ruoli" (CheckboxList Role con badge `manual`/`oidc`)
    - _Requirements: 6.3, 6.4, 6.5_
  - [ ] 10.2 Implementare la tabella con colonne: name, email, is_active (badge), is_admin (badge), numero applicazioni accessibili, last_login_at, created_at
    - _Requirements: 6.3_
  - [ ] 10.3 Creare `AccessibleApplicationsRelationManager` che mostra la lista completa delle Application accessibili all'utente con colonna "Fonte" (Diretto / Tramite ruolo: {nome})
    - _Requirements: 4.6_
  - [ ] 10.4 Registrare le modifiche ai permessi tramite `AuditLogService::logPermissionChange()` negli hook `afterSave()` della risorsa
    - _Requirements: 8.4_
  - Proteggere la risorsa con verifica `is_admin`
  - _Requirements: 3.1, 7.2_

- [ ] 11. Filament AuditLogResource (read-only)
  - Creare `App\Filament\Resources\AuditLogResource` senza form di creazione/modifica (solo `canCreate(): false`, `canEdit(): false`, `canDelete(): false`)
  - Table con colonne: event_type (badge colorato: login=blu, logout=grigio, create=verde, update=giallo, delete=rosso, permission_change=viola), user (nome), admin (nome), subject (tipo + ID), ip_address, created_at (formato relativo)
  - Filtri: event_type (select multiplo), intervallo di date, user_id
  - Action "View" (modal) con payload JSON formattato (before/after)
  - Proteggere la risorsa con verifica `is_admin`
  - _Requirements: 8.5_

- [ ] 12. Launcher Page Filament
  - Creare `App\Filament\Pages\Launcher` come Filament Page accessibile a tutti gli utenti autenticati (non solo admin)
  - Implementare griglia responsive di card (3 col desktop, 2 tablet, 1 mobile) con: icona/logo, nome, descrizione; click apre URL in nuova scheda
  - Aggiungere campo di ricerca Livewire in tempo reale che filtra per nome e descrizione
  - Mostrare messaggio "Nessuna applicazione disponibile. Contatta l'amministratore." se l'utente non ha applicazioni
  - Usare `auth()->user()->accessibleApplications()` per recuperare le app
  - Escludere la pagina dal menu di navigazione del pannello admin
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_
  - [ ] 12.1 Scrivere property test per filtro ricerca (Property 5)
    - **Property 5: Ricerca filtra per nome e descrizione**
    - **Validates: Requirements 2.6**
    - Usare Eris con generatori di stringhe per query e nomi/descrizioni; verificare che il filtro includa solo le app che contengono la query (case-insensitive)

- [ ] 13. Comando Artisan audit:prune e scheduler
  - Creare `App\Console\Commands\AuditPruneCommand` (`audit:prune`) che elimina i record `audit_logs` con `created_at < now()->subDays(90)`
  - Il comando deve essere idempotente e restituire exit code 1 in caso di errore database
  - Registrare il comando nello scheduler in `routes/console.php` (o `bootstrap/app.php`) con `->daily()`
  - _Requirements: 8.6_
  - [ ] 13.1 Scrivere property test per audit:prune (Property 22)
    - **Property 22: Il comando audit:prune elimina solo i record più vecchi di 90 giorni**
    - **Validates: Requirements 8.6**
    - Usare Eris con generatori di interi per creare N record recenti e M record vecchi arbitrari; verificare che dopo il prune rimangano esattamente i record con `created_at >= now()->subDays(90)`

- [ ] 14. Checkpoint — Verificare integrazione servizi e pannello Filament
  - Eseguire `php artisan migrate:fresh` e verificare che tutte le migration girino senza errori
  - Verificare che le risorse Filament siano registrate e accessibili nel pannello admin
  - Assicurarsi che tutti i test esistenti passino; chiedere all'utente se sorgono dubbi.

- [ ] 15. Factory per Application, Role e AuditLog
  - Creare `database/factories/ApplicationFactory.php` con stati: `active()`, `inactive()`, `expiringSoon()`, `expired()`, e generazione di tutti i campi NIS2 con Faker
  - Creare `database/factories/RoleFactory.php` con generazione di `name` univoco e `description`
  - Creare `database/factories/AuditLogFactory.php` con stati per ogni `event_type` e generazione di `payload` JSON
  - _Requirements: (supporto ai test)_

- [ ] 16. Test unitari per OidcRoleSyncService, contractStatus e accessibleApplications
  - [ ] 16.1 Creare `tests/Unit/OidcRoleSyncServiceTest.php` con test: `test_sync_assigns_matching_roles`, `test_sync_removes_oidc_roles_not_in_claim`, `test_sync_preserves_manual_roles`, `test_sync_ignores_unknown_role_names`, `test_sync_does_nothing_when_disabled`
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_
  - [ ] 16.2 Creare `tests/Unit/ApplicationContractStatusTest.php` con test per tutti i casi di `contractStatus()`: data passata, entro 30 giorni, oltre 30 giorni, null
    - _Requirements: 3.9, 3.10_
  - [ ] 16.3 Creare `tests/Unit/UserAccessibleApplicationsTest.php` con test: permesso diretto, permesso via ruolo, unione senza duplicati, app disattivata esclusa, ordinamento per sort_order, revoca permesso diretto con ruolo attivo
    - _Requirements: 2.1, 4.3, 4.4, 4.5, 2.5, 3.6_

- [ ] 17. Feature tests
  - [ ] 17.1 Creare `tests/Feature/LauncherTest.php`: `test_user_sees_only_permitted_applications`, `test_user_with_no_permissions_sees_empty_state`, `test_inactive_application_is_hidden`, `test_search_filters_applications`, `test_unauthenticated_user_is_redirected`
    - _Requirements: 2.1, 2.4, 3.6, 2.6, 7.1_
  - [ ] 17.2 Creare `tests/Feature/PermissionTest.php`: `test_direct_permission_grants_access`, `test_role_permission_grants_access`, `test_union_of_direct_and_role_permissions_has_no_duplicates`, `test_revoking_direct_permission_preserves_role_access`, `test_duplicate_permission_assignment_is_idempotent`
    - _Requirements: 4.1, 4.3, 4.4, 4.5, 4.7_
  - [ ] 17.3 Creare `tests/Feature/AuditLogTest.php`: `test_login_creates_audit_log`, `test_application_create_logs_event`, `test_application_update_logs_before_after`, `test_application_delete_logs_event`, `test_permission_change_logs_event`
    - _Requirements: 8.1, 8.3, 8.4_
  - [ ] 17.4 Creare `tests/Feature/ApplicationResourceTest.php`: `test_admin_can_create_application`, `test_admin_can_deactivate_application`, `test_delete_application_removes_permissions`, `test_contract_expiry_badge_shown`, `test_csv_export_contains_nis2_fields`
    - _Requirements: 3.2, 3.6, 3.7, 3.9, 3.12_
  - [ ] 17.5 Creare `tests/Feature/AdminAuthorizationTest.php`: `test_non_admin_cannot_access_filament_panel`, `test_disabled_user_cannot_access_launcher`
    - _Requirements: 7.2, 6.5_

- [ ] 18. Property-based tests con Eris
  - Installare `eris/eris` come dipendenza dev: `composer require --dev eris/eris`
  - [ ] 18.1 Creare `tests/Unit/Properties/UserAccessibleApplicationsPropertyTest.php` con property test per Property 2, 3, 4, 6, 11, 12, 13 usando `Eris\TestTrait`
    - **Property 2: Il Launcher mostra esattamente le applicazioni autorizzate** — Validates: Req 2.1, 4.3
    - **Property 3: Nessun duplicato nell'unione dei permessi** — Validates: Req 4.4
    - **Property 4: Ordinamento per sort_order** — Validates: Req 2.5
    - **Property 6: Applicazione disattivata non appare nel Launcher** — Validates: Req 3.6
    - **Property 11: Assegnazione ruolo concede accesso a tutte le applicazioni del ruolo** — Validates: Req 4.3
    - **Property 12: Revoca permesso diretto preserva l'accesso via ruolo** — Validates: Req 4.5
    - **Property 13: Idempotenza dell'assegnazione permesso** — Validates: Req 4.7
  - [ ] 18.2 Creare `tests/Unit/Properties/OidcRoleSyncPropertyTest.php` con property test per Property 14, 15, 16
    - **Property 14: Sincronizzazione ruoli OIDC assegna i ruoli corrispondenti** — Validates: Req 5.2
    - **Property 15: Sincronizzazione OIDC preserva i ruoli manuali** — Validates: Req 5.3
    - **Property 16: Sincronizzazione OIDC ignora ruoli sconosciuti senza errori** — Validates: Req 5.5
  - [ ] 18.3 Creare `tests/Unit/Properties/ApplicationContractStatusPropertyTest.php` con property test per Property 8, 9
    - **Property 8: Badge scadenza contratto riflette la data corrente** — Validates: Req 3.9, 3.10
    - **Property 9: Filtro contratto in scadenza è coerente con contractStatus** — Validates: Req 3.11
  - [ ] 18.4 Creare `tests/Unit/Properties/ApplicationObserverPropertyTest.php` con property test per Property 7
    - **Property 7: Eliminazione applicazione rimuove tutti i permessi associati** — Validates: Req 3.7
  - [ ] 18.5 Creare `tests/Unit/Properties/AuditPrunePropertyTest.php` con property test per Property 22
    - **Property 22: Il comando audit:prune elimina solo i record più vecchi di 90 giorni** — Validates: Req 8.6

- [ ] 19. Checkpoint finale — Tutti i test devono passare
  - Eseguire `php artisan test` e verificare che tutti i test (unit, feature, property) passino
  - Verificare che `php artisan migrate:fresh` completi senza errori
  - Assicurarsi che tutti i test passino; chiedere all'utente se sorgono dubbi.

---

## Note

- I task contrassegnati con `*` sono opzionali e possono essere saltati per un MVP più rapido
- Ogni task referenzia i requisiti specifici per la tracciabilità
- I property test usano `eris/eris` con `Eris\TestTrait` e minimo 100 iterazioni (`->withMaxSize(100)`)
- I checkpoint garantiscono la validazione incrementale prima di procedere con i task successivi
- Il Prompt 1 (setup OIDC base) è già implementato: non sono inclusi task per AuthController, rotte OIDC o configurazione Socialite di base
- L'integrazione OIDC avviene tramite `dutchcodingcompany/filament-socialite` già configurato; il callback si aggancia tramite `resolveUserUsing()` nel provider Filament
