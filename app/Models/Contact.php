<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'company',
        'role',
        'notes',
    ];

    protected static function booted()
    {
        static::creating(function ($contact) {
            $contact->extractCompanyFromEmail();
        });

        static::updating(function ($contact) {
            if ($contact->isDirty('email')) {
                $contact->extractCompanyFromEmail();
            }
        });
    }

    public function extractCompanyFromEmail()
    {
        if (empty($this->email) || !empty($this->company)) {
            return;
        }

        $domain = substr(strrchr($this->email, "@"), 1);
        
        // Domini non aziendali da ignorare
        $nonBusinessDomains = [
            'gmail.com', 'libero.it', 'yahoo.com', 'hotmail.com', 'outlook.com',
            'icloud.com', 'aol.com', 'mail.com', 'gmx.com', 'protonmail.com',
            'tiscali.it', 'virgilio.it', 'tin.it', 'alice.it', 'poste.it'
        ];

        if (in_array($domain, $nonBusinessDomains)) {
            return;
        }

        // Estrai il nome dell'azienda dal dominio
        $companyName = $this->extractCompanyNameFromDomain($domain);
        
        if ($companyName) {
            $this->company = $companyName;
        }
    }

    private function extractCompanyNameFromDomain($domain)
    {
        // Rimuovi sottodomini comuni
        $domain = preg_replace('/^(mail\.|email\.|www\.)/', '', $domain);
        
        // Gestione domini specifici
        $domainMappings = [
            'icar.cnr.it' => 'ICAR-CNR',
            'cnr.it' => 'CNR',
            'nectlc.com' => 'NECTLC',
            'vertiv.com' => 'Vertiv',
            'firekloud.it' => 'Firekloud',
            'firetek.it' => 'Firetek',
            'sonicwall.com' => 'SonicWall',
            'kaosinformatica.it' => 'Kaos Informatica',
            'linksmt.it' => 'LinksMT',
        ];

        if (isset($domainMappings[$domain])) {
            return $domainMappings[$domain];
        }

        // Per domini .gov.it
        if (preg_match('/\.gov\.it$/', $domain)) {
            return 'Amministrazione Pubblica';
        }

        // Per domini .edu.it o universitari
        if (preg_match('/(\.edu\.it$|universita|uni)/', $domain)) {
            return 'Università';
        }

        // Estrai il nome prima del primo punto
        $parts = explode('.', $domain);
        if (count($parts) >= 2) {
            $companyName = ucfirst($parts[0]);
            
            // Correggi alcune abbreviazioni comuni
            $corrections = [
                'Cnr' => 'CNR',
                'Icar' => 'ICAR',
                'Fbk' => 'FBK',
                'Polimi' => 'Politecnico di Milano',
                'Uniroma1' => 'Università La Sapienza',
            ];
            
            return $corrections[$companyName] ?? $companyName;
        }

        return null;
    }
}
