<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>NIS2 Inventory Portal</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary: #2563eb;
                --primary-dark: #1e40af;
                --bg: #f8fafc;
                --text: #1e293b;
                --text-muted: #64748b;
                --card-bg: #ffffff;
                --border: #e2e8f0;
                --accent: #f59e0b;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #0f172a;
                    --text: #f1f5f9;
                    --text-muted: #94a3b8;
                    --card-bg: #1e293b;
                    --border: #334155;
                }
            }

            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--bg);
                color: var(--text);
                line-height: 1.6;
                padding: 2rem;
            }

            .container {
                max-width: 1000px;
                margin: 0 auto;
            }

            header {
                text-align: center;
                margin-bottom: 4rem;
                animation: fadeInDown 0.8s ease-out;
            }

            h1 {
                font-family: 'Outfit', sans-serif;
                font-size: 2.5rem;
                font-weight: 700;
                margin-bottom: 1rem;
                background: linear-gradient(135deg, var(--primary), var(--accent));
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .description {
                max-width: 700px;
                margin: 0 auto;
                font-size: 1.125rem;
                color: var(--text-muted);
            }

            .card {
                background: var(--card-bg);
                border: 1px solid var(--border);
                border-radius: 1rem;
                padding: 2rem;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                margin-bottom: 2rem;
                animation: fadeInUp 0.8s ease-out;
            }

            .card-title {
                font-family: 'Outfit', sans-serif;
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 1.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 1rem;
            }

            th, td {
                padding: 1rem;
                text-align: left;
                border-bottom: 1px solid var(--border);
            }

            th {
                font-weight: 600;
                color: var(--text-muted);
                text-transform: uppercase;
                font-size: 0.75rem;
                letter-spacing: 0.05em;
            }

            tr:last-child td {
                border-bottom: none;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                padding: 0.75rem 1.5rem;
                border-radius: 0.5rem;
                font-weight: 500;
                text-decoration: none;
                transition: all 0.2s;
                cursor: pointer;
                border: none;
            }

            .btn-primary {
                background-color: var(--primary);
                color: white;
            }

            .btn-primary:hover {
                background-color: var(--primary-dark);
                transform: translateY(-1px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .btn-outline {
                background-color: transparent;
                border: 1px solid var(--border);
                color: var(--text);
            }

            .btn-outline:hover {
                background-color: var(--border);
            }

            .print-only {
                display: none;
            }

            .no-print {
                display: block;
            }

            @media print {
                body {
                    padding: 0;
                    background-color: white;
                    color: black;
                }

                .no-print {
                    display: none !important;
                }

                .print-only {
                    display: block !important;
                }

                .card {
                    box-shadow: none;
                    border: none;
                    padding: 0;
                }

                .container {
                    max-width: 100%;
                }

                table {
                    border: 1px solid #000;
                }

                th, td {
                    border-bottom: 1px solid #000;
                    color: black !important;
                }

                h1 {
                    -webkit-text-fill-color: black;
                    margin-bottom: 2rem;
                }
            }

            @keyframes fadeInDown {
                from { opacity: 0; transform: translateY(-20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @keyframes fadeInUp {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .actions {
                display: flex;
                gap: 1rem;
                justify-content: center;
                align-items: center;
                margin-top: 2rem;
            }

            .badge {
                padding: 0.25rem 0.5rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
                background-color: #fee2e2;
                color: #991b1b;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <header class="no-print">
                <h1>NIS2 Inventory HUB</h1>
                <p class="description">
                    Portale dedicato alla conformità NIS2. <br> HUB che funge da punto di raccolta per i dati gestiti dal team per garantire la sicurezza dell'infrastruttura.
                </p>
                <div class="actions">
                    @if (Route::has('filament.admin.auth.login'))
                        @auth
                            <a href="{{ url('/admin') }}" class="text-sm font-semibold text-slate-900 hover:text-blue-600 transition">Dashboard</a>
                        @else
                            <a href="{{ route('filament.admin.auth.login') }}" class="hidden sm:inline-block text-sm font-semibold text-slate-700 hover:text-blue-600 transition">Accedi</a>
                            <a href="{{ route('filament.admin.auth.login') }}" class="inline-flex justify-center items-center py-2.5 px-5 text-sm font-semibold rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-600/30 transition-all active:scale-95">Area Riservata &rarr;</a>
                        @endauth
                    @endif
                    <button onclick="window.print()" class="btn btn-outline">
                        <svg style="margin-right: 0.5rem;" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        Stampa Emergenza
                    </button>
                </div>
            </header>

            <div class="card">
                <div class="card-title">
                    <span>Contatti di Emergenza - Applicazioni Strategiche</span>
                    <span class="badge no-print">Uso Riservato</span>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Nome Applicazione</th>
                            <th>Referente Interno</th>
                            <th>Referente Esterno</th>
                            <th>Email Esterna</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($strategicApplications as $app)
                            <tr>
                                <td style="font-weight: 500;">{{ $app->name }}</td>
                                <td>{{ $app->internal_technical_contact }}</td>
                                <td>{{ $app->external_technical_contact }}</td>
                                <td><a href="mailto:{{ $app->external_technical_email }}" style="color: var(--primary); text-decoration: none;">{{ $app->external_technical_email }}</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                                    Nessuna applicazione strategica censita.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <footer class="no-print" style="text-align: center; margin-top: 4rem; color: var(--text-muted); font-size: 0.875rem;">
                &copy; {{ date('Y') }} NIS2 Inventory Portal. Helpdesk fossr-na@icar.cnr.it.
            </footer>
        </div>
    </body>
</html>
