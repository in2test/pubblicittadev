<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0e0b0d">
    <meta name="robots" content="noindex, nofollow">
    <title>Manutenzione in corso | Pubblicittà24</title>
    <style>
        :root {
            color-scheme: dark;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0e0b0d;
            color: #f4f1f2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-width: 320px;
            min-height: 100vh;
            margin: 0;
            display: grid;
            place-items: center;
            padding: 32px 20px;
            background:
                radial-gradient(ellipse at 50% 0, rgb(205 55 50 / 12%), transparent 58%),
                #0e0b0d;
        }

        main {
            width: min(100%, 720px);
            border: 1px solid #382e33;
            background: #1c171a;
        }

        .top-line {
            height: 5px;
            background: #cd3732;
        }

        .content {
            padding: clamp(28px, 7vw, 64px);
        }

        .brand,
        .eyebrow,
        .status-label,
        .footer {
            font-family: "Roboto Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
            text-transform: uppercase;
            letter-spacing: 0.16em;
        }

        .brand {
            margin: 0 0 56px;
            color: #f4f1f2;
            font-size: 13px;
            font-weight: 800;
        }

        .brand span {
            color: #cd3732;
        }

        .eyebrow {
            margin: 0 0 16px;
            color: #d75f5b;
            font-size: 11px;
            font-weight: 700;
        }

        h1 {
            max-width: 580px;
            margin: 0;
            font-size: clamp(38px, 9vw, 72px);
            font-weight: 900;
            line-height: 0.98;
            letter-spacing: -0.055em;
            text-transform: uppercase;
        }

        h1 span {
            color: #cd3732;
        }

        .message {
            max-width: 470px;
            margin: 24px 0 0;
            color: #d1c7cc;
            font-size: 16px;
            line-height: 1.7;
        }

        .status {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-top: 40px;
            padding: 18px 20px;
            border: 1px solid #54454d;
            border-left: 3px solid #cd3732;
            background: #0e0b0d;
        }

        .status-mark {
            flex: 0 0 10px;
            width: 10px;
            height: 10px;
            background: #cd3732;
        }

        .status-label {
            display: block;
            margin-bottom: 5px;
            color: #a38f99;
            font-size: 9px;
        }

        .status strong {
            font-size: 14px;
            font-weight: 700;
        }

        .footer {
            margin: 36px 0 0;
            color: #8c7380;
            font-size: 9px;
        }

        @media (max-width: 480px) {
            .brand {
                margin-bottom: 44px;
            }

            .status {
                align-items: flex-start;
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <main>
        <div class="top-line" aria-hidden="true"></div>
        <div class="content">
            <p class="brand">Pubblicittà<span>24</span></p>
            <p class="eyebrow">Intervento in corso · 503</p>
            <h1>Torniamo <span>subito.</span></h1>
            <p class="message">
                Stiamo effettuando alcuni aggiornamenti per offrirti un servizio migliore.
                Riprova tra un paio di minuti: il sito tornerà presto disponibile.
            </p>
            <div class="status" role="status">
                <span class="status-mark" aria-hidden="true"></span>
                <div>
                    <span class="status-label">Stato del servizio</span>
                    <strong>Manutenzione temporanea</strong>
                </div>
            </div>
            <p class="footer">Grazie per la pazienza.</p>
        </div>
    </main>
</body>
</html>
