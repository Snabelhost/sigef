<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing: antialiased;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8f9fa;">
        <tr>
            <td align="center" style="padding: 32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="520" style="max-width: 520px; width: 100%;">

                    {{-- Logo --}}
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <img src="{{ $message->embed(public_path('images/logo-sigef.png')) }}" alt="SIGEF" width="56" height="56" style="display: block; border-radius: 12px;" />
                        </td>
                    </tr>

                    {{-- Card Principal --}}
                    <tr>
                        <td style="background-color: #ffffff; border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden;">

                            {{-- Barra azul no topo --}}
                            <div style="height: 4px; background: #1e3a5f;"></div>

                            {{-- Conteúdo --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="padding: 32px 36px 28px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 20px; font-size: 15px; color: #374151; line-height: 1.7;">
                                            Olá <strong>{{ $userName }}</strong>,
                                        </p>
                                        <p style="margin: 0 0 24px; font-size: 14px; color: #6b7280; line-height: 1.7;">
                                            {{ $introText }}
                                        </p>
                                    </td>
                                </tr>

                                {{-- Credenciais --}}
                                <tr>
                                    <td style="padding-bottom: 24px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">
                                            <tr>
                                                <td style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                                                    <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px;">E-mail</span>
                                                    <br>
                                                    <span style="font-size: 14px; color: #111827; font-weight: 500;">{{ $userEmail }}</span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 16px 20px;">
                                                    <span style="font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.8px;">Palavra-passe</span>
                                                    <br>
                                                    <span style="font-size: 11px; color: #6b7280; display: block; margin-top: 4px;">🔒 Selecione o campo abaixo para revelar a sua palavra-passe:</span>
                                                    <code style="font-size: 15px; color: #e5e7eb; font-weight: 600; font-family: 'SF Mono', 'Fira Code', 'Courier New', monospace; background-color: #e5e7eb; padding: 6px 12px; border-radius: 4px; display: inline-block; margin-top: 6px; letter-spacing: 0.5px; border: 1px dashed #d1d5db; cursor: text;">{{ $plainPassword }}</code>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                {{-- Botão --}}
                                <tr>
                                    <td align="center" style="padding-bottom: 24px;">
                                        <a href="{{ $loginUrl }}" target="_blank" style="display: inline-block; background-color: #1e3a5f; color: #ffffff; font-size: 14px; font-weight: 600; text-decoration: none; padding: 12px 32px; border-radius: 6px;">
                                            Aceder ao SIGEF
                                        </a>
                                    </td>
                                </tr>

                                {{-- Aviso --}}
                                <tr>
                                    <td>
                                        <p style="margin: 0; font-size: 12px; color: #9ca3af; line-height: 1.6; border-top: 1px solid #f3f4f6; padding-top: 16px;">
                                            ⚠ Por segurança, recomendamos que altere a sua palavra-passe no primeiro acesso.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding: 20px 0 0;">
                            <p style="margin: 0; font-size: 11px; color: #a1a1aa;">
                                © {{ date('Y') }} SIGEF · Polícia Nacional de Angola
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>

</html>