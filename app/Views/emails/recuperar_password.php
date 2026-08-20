<?php
/**
 * MAIL DE RECUPERACIÓN DE CONTRASEÑA.
 * Lo arma AccesoController::procesarRecuperacion() y lo manda por SMTP.
 *
 * Recibe:
 *   $nombre         string  Nombre del usuario (para el saludo).
 *   $enlace         string  URL con el token de un solo uso.
 *   $minutos        int     Cuánto vale el enlace.
 *   $logoCid        string  Content-ID del logo verde  (fondo claro).
 *   $logoClaroCid   string  Content-ID del logo crema  (fondo oscuro).
 *
 * POR QUÉ EL LOGO VA COMO CID Y NO COMO <img src="http://...">:
 * un mail se abre desde el celular, fuera de esta red. Una URL a localhost
 * ahí no existe y la imagen queda rota. Con CID el PNG viaja adentro del
 * propio correo, así que se ve siempre. Si por lo que sea no llegó el CID,
 * cae a la URL absoluta y al menos funciona desde esta misma máquina.
 *
 * REGLAS DE UN MAIL HTML (no es una página web):
 *   - Todo con <table> y estilos en línea: los clientes de correo no
 *     entienden flex, grid ni hojas de estilo externas.
 *   - Nada de position:absolute. Gmail borra esa propiedad y lo que estaba
 *     "flotando" cae al medio del texto. Los anillos del hero son un
 *     degradado del fondo justamente por eso.
 *   - Los <style> del <head> sirven para el modo oscuro y el celular
 *     (@media), pero el diseño tiene que verse bien sin ellos.
 *   - Outlook ignora border-radius y degradados: el botón lleva su versión
 *     VML en el bloque <!--[if mso]-->.
 */
$logoCid      = $logoCid      ?? '';
$logoClaroCid = $logoClaroCid ?? '';

$srcLogo  = $logoCid !== ''      ? 'cid:' . $logoCid      : base_url('assets/img/branding/mark-email.png');
$srcClaro = $logoClaroCid !== '' ? 'cid:' . $logoClaroCid : base_url('assets/img/branding/mark-email-light.png');
?>
<!DOCTYPE html>
<html lang="es" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Restablecé tu contraseña — EdenAir</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&amp;family=DM+Serif+Display&amp;family=DM+Mono:wght@400;500&amp;display=swap" rel="stylesheet">
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; line-height: 100%; outline: none; text-decoration: none; display: block; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; }
        a { text-decoration: none; }

        /* ÚNICA animación del mail, y a propósito.
           Casi ningún cliente de correo anima: Gmail borra los @keyframes, y
           solo Apple Mail y algún otro los respetan. Ésta mueve el logo con
           transform, así que donde no corre simplemente queda quieto.
           No usar animaciones que arranquen en opacity:0 (con fill-mode
           'both'): si el cliente se come los keyframes, ese texto no se ve
           nunca. Para que se mueva en Gmail hay una sola forma: un GIF. */
        @keyframes ea-bob { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
        .ea-logo { animation: ea-bob 5.5s ease-in-out infinite; }

        @media (prefers-reduced-motion: reduce) {
            .ea-logo { animation: none !important; }
        }

        @media only screen and (max-width: 600px) {
            .em-container { width: 100% !important; }
            .em-card { border-radius: 0 !important; }
            .em-pad  { padding-left: 26px !important; padding-right: 26px !important; }
            .em-btn a { display: block !important; }
            .em-h1 { font-size: 28px !important; line-height: 34px !important; }
        }

        @media (prefers-color-scheme: dark) {
            .em-bg      { background-color: #0E1F17 !important; background-image: none !important; }
            .em-hero    { background-color: #12241A !important; background-image: radial-gradient(120% 130% at 50% -20%, #1C4A38 0%, #143326 45%, #1A2C23 82%) !important; }
            .em-card    { background-color: #1A2C23 !important; border-color: #2B5638 !important; }
            .em-text    { color: #DCEAE0 !important; }
            .em-muted   { color: #9DB4A2 !important; }
            .em-h1, .em-brand { color: #F4F1EA !important; }
            .em-info    { background-color: #12241A !important; border-color: #2B5638 !important; }
            .em-linkbox { background-color: #12241A !important; border-color: #2B5638 !important; }
            .em-linkurl { color: #8FD6C8 !important; }
            .em-eyebrow { color: #6FD3B8 !important; }   /* la volanta en verde oscuro no se leia */
            .em-hr      { border-color: #2B5638 !important; }
            .em-badge   { background-color: rgba(143, 214, 200, 0.14) !important; }
            .em-mark-light { display: block !important; }
            .em-mark-dark  { display: none !important; }
        }
    </style>
</head>
<body class="em-bg" style="margin:0; padding:0; background-color:#EEF7F4;">

    <?php /* Línea de vista previa: es lo que asoma en la lista de correos. */ ?>
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#EEF7F4; opacity:0;">
        Pediste restablecer tu contraseña de EdenAir. El enlace vale <?= esc((string) $minutos) ?> minutos.&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-bg" style="background-color:#EEF7F4; background-image:linear-gradient(180deg, #E4F3EE 0%, #EEF7F4 40%, #F6F4EC 100%);">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" class="em-container" style="width:600px; max-width:600px;">

                    <!-- =========================== TARJETA =========================== -->
                    <tr>
                        <td>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-card" style="background-color:#FFFFFF; border:1px solid #E1EFE8; border-radius:26px;">

                                <!-- Hero: la marca sobre el halo de aire.
                                     Los anillos son parte del degradado del recuadro
                                     (circle at 50% 52%), no elementos flotando. -->
                                <tr>
                                    <td class="em-hero" align="center" style="padding:34px 0 18px 0; border-radius:26px 26px 0 0; background-color:#E4F3EE; background-image:radial-gradient(120% 130% at 50% -20%, #C9EDE2 0%, #E4F3EE 45%, #FFFFFF 82%);">

                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" valign="middle" height="118" style="width:220px; height:118px; background-image:radial-gradient(circle at 50% 52%, rgba(122,196,178,0) 40%, rgba(122,196,178,0.45) 41%, rgba(122,196,178,0) 43%, rgba(122,196,178,0) 60%, rgba(122,196,178,0.30) 61%, rgba(122,196,178,0) 63%, rgba(122,196,178,0) 80%, rgba(122,196,178,0.18) 81%, rgba(122,196,178,0) 83%);">
                                                    <img class="ea-logo em-mark-dark" src="<?= esc($srcLogo) ?>" width="78" height="49" alt="EdenAir" style="width:78px; height:49px; margin:0 auto;">
                                                    <img class="ea-logo em-mark-light" src="<?= esc($srcClaro) ?>" width="78" height="49" alt="EdenAir" style="width:78px; height:49px; margin:0 auto; display:none;">
                                                </td>
                                            </tr>
                                        </table>

                                        <div class="em-brand" style="font-family:'DM Serif Display', Georgia, serif; font-size:22px; line-height:26px; color:#143326;">EdenAir</div>
                                        <div style="font-family:'DM Mono', 'Courier New', monospace; font-size:10px; letter-spacing:0.2em; text-transform:uppercase; color:#4A9B8A; line-height:16px; padding-top:4px;">Monitoreo Ambiental</div>
                                    </td>
                                </tr>

                                <!-- Título e introducción -->
                                <tr>
                                    <td class="em-pad" style="padding:30px 46px 0 46px; text-align:center;">
                                        <div class="em-eyebrow" style="font-family:'DM Mono', 'Courier New', monospace; font-size:11px; letter-spacing:0.16em; text-transform:uppercase; color:#25806D;">Seguridad de la cuenta</div>
                                        <h1 class="em-h1" style="margin:12px 0 0 0; font-family:'DM Serif Display', Georgia, serif; font-weight:400; font-size:34px; line-height:40px; color:#143326;">Restablecé tu contraseña</h1>
                                        <p class="em-text" style="margin:16px auto 0 auto; max-width:420px; font-family:'DM Sans', Arial, sans-serif; font-size:16px; line-height:26px; color:#52645A;">
                                            Hola <?= esc($nombre) ?>, recibimos tu solicitud. Creá una nueva contraseña y volvé a tu ambiente EdenAir.
                                        </p>
                                    </td>
                                </tr>

                                <!-- Botón principal (VML aparte para Outlook) -->
                                <tr>
                                    <td class="em-pad" style="padding:28px 46px 0 46px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" class="em-btn">
                                            <tr>
                                                <td align="center" style="border-radius:100px; background-color:#328A72; background-image:linear-gradient(100deg, #2F6B4F 0%, #3E9B84 100%);">
                                                    <!--[if mso]>
                                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="<?= esc($enlace) ?>" style="height:54px; v-text-anchor:middle; width:508px;" arcsize="50%" strokecolor="#2F6B4F" fillcolor="#328A72">
                                                        <w:anchorlock/>
                                                        <center style="color:#ffffff; font-family:Arial, sans-serif; font-size:16px; font-weight:bold;">Restablecer contraseña</center>
                                                    </v:roundrect>
                                                    <![endif]-->
                                                    <!--[if !mso]><!-- -->
                                                    <a href="<?= esc($enlace) ?>" style="display:block; padding:17px 24px; font-family:'DM Sans', Arial, sans-serif; font-size:16px; font-weight:600; line-height:20px; color:#ffffff; border-radius:100px; text-align:center;">Restablecer contraseña</a>
                                                    <!--<![endif]-->
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Las dos cosas que el usuario necesita saber -->
                                <tr>
                                    <td class="em-pad" style="padding:24px 46px 0 46px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-info" style="background-color:#EEF7F4; border:1px solid #D6EBE2; border-radius:16px;">
                                            <tr>
                                                <td style="padding:16px 18px 10px 18px;" valign="top">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="42" valign="top">
                                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td width="32" height="32" align="center" valign="middle" class="em-badge" style="width:32px; height:32px; background-color:#D3EEE4; border-radius:10px;">
                                                                            <?php /* Los íconos van como SVG en data:URI. Son 200 bytes y
                                                                                     no hay que adjuntar dos archivos más. */ ?>
                                                                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='17' height='17' viewBox='0 0 24 24' fill='none' stroke='%232E9E86' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='9'/%3E%3Cpath d='M12 7v5l3 2'/%3E%3C/svg%3E" width="17" height="17" alt="" style="width:17px; height:17px;">
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td valign="middle" style="padding-left:12px;">
                                                                <span class="em-text" style="font-family:'DM Sans', Arial, sans-serif; font-size:14px; line-height:20px; color:#14231B;">
                                                                    <span style="font-weight:600;">El enlace vence en <?= esc((string) $minutos) ?> minutos.</span>
                                                                    <span class="em-muted" style="color:#52645A;">Después, pedí uno nuevo desde el inicio de sesión.</span>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr><td style="padding:0 18px;"><div class="em-hr" style="border-top:1px solid #D6EBE2; font-size:0; line-height:0;">&nbsp;</div></td></tr>
                                            <tr>
                                                <td style="padding:10px 18px 16px 18px;" valign="top">
                                                    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                        <tr>
                                                            <td width="42" valign="top">
                                                                <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                                                    <tr>
                                                                        <td width="32" height="32" align="center" valign="middle" class="em-badge" style="width:32px; height:32px; background-color:#D3EEE4; border-radius:10px;">
                                                                            <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='17' height='17' viewBox='0 0 24 24' fill='none' stroke='%232E9E86' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z'/%3E%3C/svg%3E" width="17" height="17" alt="" style="width:17px; height:17px;">
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </td>
                                                            <td valign="middle" style="padding-left:12px;">
                                                                <span class="em-text" style="font-family:'DM Sans', Arial, sans-serif; font-size:14px; line-height:20px; color:#14231B;">
                                                                    <span style="font-weight:600;">¿No fuiste vos?</span>
                                                                    <span class="em-muted" style="color:#52645A;">Ignorá este correo: tu contraseña actual sigue intacta.</span>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="em-pad" style="padding:26px 46px 0 46px;">
                                        <div class="em-hr" style="border-top:1px solid #EDEFE7; font-size:0; line-height:0;">&nbsp;</div>
                                    </td>
                                </tr>

                                <!-- Por si el botón no anda (algunos clientes lo bloquean) -->
                                <tr>
                                    <td class="em-pad" style="padding:18px 46px 40px 46px;">
                                        <p class="em-muted" style="margin:0 0 10px 0; font-family:'DM Sans', Arial, sans-serif; font-size:13px; line-height:20px; color:#6E7D73;">Si el botón no funciona, copiá y pegá este enlace:</p>
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="em-linkbox" style="background-color:#F5FBF8; border:1px solid #E1EFE8; border-radius:10px;">
                                            <tr>
                                                <td style="padding:12px 14px; word-break:break-all;">
                                                    <a href="<?= esc($enlace) ?>" class="em-linkurl" style="font-family:'DM Mono', 'Courier New', monospace; font-size:12px; line-height:19px; color:#25806D; word-break:break-all;"><?= esc($enlace) ?></a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>

                    <!-- =========================== PIE =========================== -->
                    <tr>
                        <td class="em-pad" style="padding:24px 46px 8px 46px;" align="center">
                            <?php /* Lleva em-brand para que en modo oscuro no quede verde sobre verde. */ ?>
                            <div class="em-brand" style="font-family:'DM Serif Display', Georgia, serif; font-size:15px; color:#143326; line-height:20px;">EdenAir</div>
                            <div class="em-muted" style="font-family:'DM Sans', Arial, sans-serif; font-size:12px; color:#6E7D73; line-height:19px; padding-top:6px;">Respirá mejor, viví más cómodo.</div>
                            <div class="em-muted" style="font-family:'DM Sans', Arial, sans-serif; font-size:11px; color:#9DB4A2; line-height:18px; padding-top:12px;">Correo automático · no es necesario responderlo.</div>
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
