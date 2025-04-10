<?php
namespace local_recibeexamen;

defined('MOODLE_INTERNAL') || die();

class pdf_helper {

    public static function generar_y_enviar_justificante(array $datos): bool {
        global $CFG;

        require_once($CFG->libdir . '/pdflib.php');

        $user = $datos['user'];
        $dni = $datos['dni'] ?? obtener_numdocumento_ldap($user->username) ?? 'N/D';
        $fecha = userdate(time(), '%d de %B de %Y');
        $plan = $datos['plan'];
        $asignatura = $datos['asignatura'];
        $fechainicio = $datos['fechainicio'];
        $fechafin = $datos['fechafin'];
        $sede = $datos['sede'];
        $exacodnum = $datos['codigo'];

        // Crear PDF
        $pdf = new \pdf();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('UDIMA');
        $pdf->SetTitle('Justificante de asistencia');
        $pdf->SetSubject('Justificante');
        $pdf->SetMargins(20, 30, 20);
        $pdf->AddPage();

        // Logo
        $logopath = $CFG->dirroot . '/local/recibeexamen/pix/udima_logo.png';
        if (file_exists($logopath)) {
            $pdf->Image($logopath, 15, 10, 30);
        }

        // HTML
        $html = "
        <style>
            .title { font-size: 16pt; font-weight: bold; text-align: center; margin-bottom: 20px; }
            .text { font-size: 12pt; text-align: justify; }
            .info { font-size: 11pt; }
        </style>

        <div class='title'>JUSTIFICANTE DE ASISTENCIA A EXAMEN</div>

        <div class='text'>
            Collado Villalba, a {$fecha}<br><br>
            D/Dª <strong>" . fullname($user) . "</strong> con Número de Documento de Identificación: <strong>{$dni}</strong>,
            matriculado/a en esta Universidad en estudios universitarios conducentes a una titulación oficial, ha asistido a
            la realización del examen convocado por la Universidad a Distancia de Madrid, en la fecha, hora y sede que figura
            a continuación, expidiéndose a petición del interesado el presente certificado a los efectos oportunos.
        </div><br>

        <div class='info'>
            <strong>Información relativa al examen:</strong><br><br>
            <strong>Código examen:</strong> {$exacodnum}<br>
            <strong>Titulación:</strong> {$plan}<br>
            <strong>Asignatura:</strong> {$asignatura}<br>
            <strong>Fecha y hora de inicio:</strong> {$fechainicio}<br>
            <strong>Fecha y hora de finalización:</strong> {$fechafin}<br>
            <strong>Sede:</strong> {$sede}<br>
        </div><br><br>

        <div class='text'>Firma y sello</div><br><br>
        ";

        $pdf->SetFont('helvetica', '', 12);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Firma
        $firmapath = $CFG->dirroot . '/local/recibeexamen/pix/firma.png';
        if (file_exists($firmapath)) {
            $pdf->Image($firmapath, 20, $pdf->GetY(), 50);
        }

        $pdf->Ln(40);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->MultiCell(0, 10, "Carretera de La Coruña, km 38,500 • 28400 Collado Villalba (Madrid) • 902 02 00 03\nwww.udima.es • informa@udima.es", 0, 'C');

        // Guardar temporal y enviar
        $filename = "justificante_{$user->username}.pdf";
        $tempdir = make_temp_directory('local_recibeexamen');
        $pdfpath = $tempdir . '/' . $filename;
        $pdf->Output($pdfpath, 'F');

        // Enviar por correo
        $subject = "Justificante - {$user->username}";
        $message = "Estimado/a {$user->firstname},\n\nAdjunto le remitimos el justificante de asistencia al examen.\n\nSaludos cordiales.";

        $enviado = email_to_user(
            $user,
            \core_user::get_support_user(),
            $subject,
            $message,
            $message,
            $pdfpath,
            $filename
        );

        @unlink($pdfpath);

        return $enviado;
    }
}
