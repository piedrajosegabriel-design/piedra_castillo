<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer contrasena</title>
</head>
<body style="margin:0; padding:0; background:#f4f1ea; font-family:Arial, Helvetica, sans-serif; color:#14201a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f1ea; margin:0; padding:0;">
        <tr>
            <td align="center" style="padding:38px 16px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:590px; background:#ffffff; border:1px solid #dbe6d9; border-radius:22px; overflow:hidden;">
                    <tr>
                        <td style="background:#0e1f17; padding:32px 34px 30px 34px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td valign="middle" style="width:56px;">
                                        <div style="width:46px; height:46px; border:2px solid #ecf2e8; border-radius:50%; color:#ecf2e8; font-size:26px; line-height:42px; text-align:center; font-family:Georgia, 'Times New Roman', serif;">
                                            e
                                        </div>
                                    </td>
                                    <td valign="middle">
                                        <div style="font-size:25px; line-height:1; font-weight:800; color:#ecf2e8; letter-spacing:0;">
                                            Eden<span style="font-family:Georgia, 'Times New Roman', serif; font-style:italic; font-weight:400;">Air</span>
                                        </div>
                                        <div style="margin-top:7px; font-size:12px; line-height:1.4; color:#bcd2bd; text-transform:uppercase; letter-spacing:1.6px;">
                                            Recuperacion de cuenta
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:42px 38px 12px 38px; background:#fbfaf5;">
                            <div style="display:inline-block; padding:7px 12px; background:#ecf2e8; border:1px solid #dbe6d9; border-radius:999px; color:#2b5638; font-size:12px; line-height:1; font-weight:700; text-transform:uppercase; letter-spacing:1.1px;">
                                Seguridad
                            </div>
                            <h1 style="margin:18px 0 0 0; color:#14201a; font-family:Georgia, 'Times New Roman', serif; font-size:35px; line-height:1.08; font-weight:400; letter-spacing:0;">
                                Recupera tu acceso.
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:8px 38px 0 38px; background:#fbfaf5;">
                            <p style="margin:0; color:#14201a; font-size:16px; line-height:1.65;">
                                Hola <?= esc($nombre) ?>,
                            </p>
                            <p style="margin:14px 0 0 0; color:#3b4a40; font-size:16px; line-height:1.65;">
                                Recibimos una solicitud para crear una nueva contrasena para tu cuenta de EdenAir. Para continuar, usa este enlace temporal.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="left" style="padding:30px 38px 28px 38px; background:#fbfaf5;">
                            <a href="<?= esc($enlace, 'attr') ?>" style="display:inline-block; background:#1c4029; color:#ecf2e8; text-decoration:none; font-size:15px; line-height:1; font-weight:800; padding:16px 24px; border-radius:999px;">
                                Restablecer contrasena
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 38px 32px 38px; background:#fbfaf5;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#ecf2e8; border:1px solid #dbe6d9; border-radius:14px;">
                                <tr>
                                    <td style="padding:18px 20px;">
                                        <p style="margin:0; color:#14201a; font-size:14px; line-height:1.6; font-weight:800;">
                                            El enlace vence en <?= esc((string) $minutos) ?> minutos.
                                        </p>
                                        <p style="margin:7px 0 0 0; color:#3b4a40; font-size:14px; line-height:1.6;">
                                            Despues de usarlo, el token queda anulado. Si no pediste este cambio, podes ignorar este correo.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 38px 38px 38px; background:#fbfaf5;">
                            <p style="margin:0; color:#6f7c70; font-size:13px; line-height:1.6;">
                                Si el boton no funciona, abri este enlace:
                            </p>
                            <p style="margin:8px 0 0 0; color:#6f7c70; font-size:12px; line-height:1.5; word-break:break-all;">
                                <a href="<?= esc($enlace, 'attr') ?>" style="color:#1c4029; text-decoration:underline;"><?= esc($enlace) ?></a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#ece7db; border-top:1px solid #dbe6d9; padding:22px 38px;">
                            <p style="margin:0; color:#6f7c70; font-size:12px; line-height:1.6;">
                                EdenAir - Respira mejor, vivi mas comodo.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
