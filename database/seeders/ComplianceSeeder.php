<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Contact;
use App\Models\Incident;
use App\Models\MaintenanceIntervention;
use App\Models\Role;
use App\Models\Vulnerability;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplianceSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = [
            ['name' => 'System Administrator', 'description' => 'Accesso completo ai sistemi e alle configurazioni infrastrutturali.'],
            ['name' => 'Compliance Officer', 'description' => 'Responsabile della verifica dei parametri NIS2 e GDPR.'],
            ['name' => 'HPC Scientist', 'description' => 'Utilizzatore delle risorse di calcolo ad alte prestazioni.'],
            ['name' => 'Network Engineer', 'description' => 'Gestione della connettività e dei perimetri di sicurezza.'],
            ['name' => 'Scientific Board Member', 'description' => 'Membro del comitato di indirizzo scientifico.'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }

        // 2. Contacts (Rubrica)
        $contacts = [
            [
                'name' => 'Mario Rossi',
                'email' => 'mario.rossi@icar.cnr.it',
                'phone' => '+39 081 1234567',
                'company' => 'ICAR-CNR',
                'role' => 'CISO',
                'notes' => 'Referente principale per la sicurezza NIS2.',
            ],
            [
                'name' => 'Laura Bianchi',
                'email' => 'l.bianchi@dell.com',
                'phone' => '+39 347 9876543',
                'company' => 'Dell Technologies',
                'role' => 'VENDOR_SUPPORT',
                'notes' => 'Supporto tecnico hardware per cluster HPC.',
            ],
            [
                'name' => 'Giuseppe Verdi',
                'email' => 'g.verdi@pec.it',
                'phone' => '+39 06 55443322',
                'company' => 'ACN - Agenzia Cybersicurezza',
                'role' => 'COMPLIANCE_OFFICER',
                'notes' => 'Contatto per notifiche incidenti rilevanti.',
            ],
            [
                'name' => 'Anna Neri',
                'email' => 'anna.neri@fastweb.it',
                'phone' => '+39 02 11223344',
                'company' => 'Fastweb SPA',
                'role' => 'IT_MANAGER',
                'notes' => 'Referente per la connettività e i contratti fibra.',
            ],
        ];

        foreach ($contacts as $contact) {
            Contact::firstOrCreate(['email' => $contact['email']], $contact);
        }

        // 3. Get some applications to link
        $apps = Application::all();
        if ($apps->isEmpty()) {
            return;
        }

        $app1 = $apps->first();
        $app2 = $apps->skip(1)->first() ?? $app1;

        // 4. Incidents
        $incidents = [
            [
                'application_id' => $app1->id,
                'title' => 'Rilevamento Tentativo Ransomware HPC-Console',
                'incident_type' => 'malware',
                'severity' => 'high',
                'status' => 'resolved',
                'detected_at' => now()->subDays(10),
                'resolved_at' => now()->subDays(9),
                'description' => 'Rilevato eseguibile sospetto nella cartella /tmp del server di gestione. Bloccato dall\'antivirus.',
                'acn_notified' => true,
                'acn_notification_date' => now()->subDays(10),
                'acn_protocol_number' => 'ACN-2026-X123',
                'impact_analysis' => 'Nessun dato cifrato. Solo isolamento temporaneo del nodo di management.',
            ],
            [
                'application_id' => $app2->id,
                'title' => 'Anomalia Alimentazione Data Center - Rack A12',
                'incident_type' => 'hardware_failure',
                'severity' => 'medium',
                'status' => 'closed',
                'detected_at' => now()->subDays(2),
                'resolved_at' => now()->subDays(2),
                'description' => 'Interruzione linea elettrica primaria. Intervento degli UPS avvenuto correttamente.',
                'acn_notified' => false,
            ],
        ];

        foreach ($incidents as $incident) {
            Incident::create($incident);
        }

        // 5. Vulnerabilities
        $vulnerabilities = [
            [
                'application_id' => $app1->id,
                'cve_id' => 'CVE-2024-3400',
                'title' => 'Palo Alto Networks PAN-OS: Command Injection Vulnerability',
                'severity' => 'critical',
                'status' => 'resolved',
                'discovery_date' => now()->subDays(30),
                'resolved_at' => now()->subDays(25),
                'description' => 'Vulnerabilità critica nel gateway VPN.',
                'remediation_plan' => 'Aggiornamento immediato al firmware 11.1.2-h3.',
            ],
            [
                'application_id' => $app2->id,
                'cve_id' => 'CVE-2023-46604',
                'title' => 'Apache ActiveMQ RCE Vulnerability',
                'severity' => 'high',
                'status' => 'fixing',
                'discovery_date' => now()->subDays(5),
                'description' => 'Esecuzione remota di codice via protocollo OpenWire.',
                'remediation_plan' => 'Migrazione alla versione 5.18.3 prevista per fine settimana.',
            ],
        ];

        foreach ($vulnerabilities as $v) {
            Vulnerability::create($v);
        }

        // 6. Maintenance Interventions
        $maintenances = [
            [
                'application_id' => $app1->id,
                'intervention_type' => 'software',
                'company_name' => 'ICAR-Internal IT',
                'operator_name' => 'Luca Bianchi',
                'description' => 'Ciclo mensile di patching OS e aggiornamento Docker containers.',
                'started_at' => now()->subDays(15),
                'ended_at' => now()->subDays(15),
                'status' => 'completed',
            ],
            [
                'application_id' => $app2->id,
                'intervention_type' => 'hardware',
                'company_name' => 'Schneider Electric',
                'operator_name' => 'Tecnico Inviato',
                'description' => 'Sostituzione batterie esauste gruppo di continuità APC 5000.',
                'started_at' => now()->addDays(5),
                'status' => 'planned',
                'notes' => 'Richiesto accesso fisico al locale server alle ore 09:00.',
            ],
        ];

        foreach ($maintenances as $m) {
            MaintenanceIntervention::create($m);
        }
    }
}
