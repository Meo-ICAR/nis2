<x-mail::message>
# Configurazione SSO per {{ $application->name }}

Gentile referente tecnico,

è stato abbinato alla gestione tecnica dell'applicazione **{{ $application->name }}** nel nostro portale NIS2.

Per procedere con l'integrazione SSO tramite WSO2, segua questi passaggi:

1. **Registrazione Portale**: Se non lo ha già fatto, la invitiamo a registrarsi sul portale FOSSR:
<x-mail::button :url="'https://fossr-portale.na.icar.cnr.it/'">
Registrati su FOSSR
</x-mail::button>

2. **Dashboard di Amministrazione**: Può accedere alla gestione dell'applicazione tramite il seguente link:
[Dashboard di Amministrazione]({{ $application->management_url ?: config('app.url') }})

3. **Credenziali SSO**: Di seguito le credenziali da includere nella sua applicazione per abilitare l'autenticazione tramite WSO2:
- **Client ID**: `{{ $application->client_id }}`
- **Client Secret**: `{{ $application->client_secret }}`

Grazie per la collaborazione,
Il team tecnico ICAR CNR
</x-mail::message>
