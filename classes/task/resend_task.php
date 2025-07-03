<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_recibeexamen\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/pdflib.php');

class resend_task extends \core\task\adhoc_task {

    public function execute() {
        global $DB, $CFG;

        $entry = null;

        try {
            $customdata = $this->get_custom_data();
            if (empty($customdata->queueid)) {
                throw new \moodle_exception('missingqueueid', 'local_recibeexamen');
            }

            $entry = $DB->get_record('local_recibeexamen_queue', ['id' => $customdata->queueid]);
            if (!$entry) {
                throw new \moodle_exception('entrynotfound', 'local_recibeexamen');
            }

            $params = json_decode($entry->data, true);

            if (!$user = $DB->get_record('user', ['username' => $params['idusuldap']])) {
                throw new \moodle_exception('usernotfound', 'local_recibeexamen');
            }

            $dniprs      = $params['dniprs'] ?? '';
            $exacodnum   = $params['exacodnum'] ?? '';
            $planomid1   = $params['planomid1'] ?? '';
            $assnomid1   = $params['assnomid1'] ?? '';
            $fechainicio = $params['fechainicio'] ?? '';
            $fechafin    = $params['fechafin'] ?? '';
            $sede        = $params['sede'] ?? '';

            $pdf = new \pdf();
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('UDIMA');
            $pdf->SetTitle('Justificante de asistencia');
            $pdf->SetSubject('Justificante');
            $pdf->SetMargins(20, 30, 20);
            $pdf->AddPage();

            $logopath = $CFG->dirroot . '/local/recibeexamen/pix/udima_logo.png';
            if (file_exists($logopath)) {
                $pdf->Image($logopath, 15, 10, 30);
            }

            $fecha = userdate(time(), '%d de %B de %Y');
            $html = '<div class="title">JUSTIFICANTE DE ASISTENCIA A EXAMEN</div>
            <div class="text">
            Collado Villalba, a ' . $fecha . '<br><br>
            D/Dª <strong>' . $user->firstname . ' ' . $user->lastname . '</strong> con Número de Documento de Identificación: <strong>' . $dniprs  . '</strong>,
            matriculado/a en esta Universidad en estudios universitarios conducentes a una titulación oficial, ha asistido a
            la realización del examen convocado por la Universidad a Distancia de Madrid, en la fecha, hora y sede que figura
            a continuación, expidiéndose a petición del interesado el presente certificado a los efectos oportunos.
            </div><br>
            <div class="info">
                <strong>Información relativa al examen:</strong><br><br>
                <strong>Código examen:</strong> ' . $exacodnum . '<br>
                <strong>Titulación:</strong> '. $planomid1 .'<br>
                <strong>Asignatura:</strong> ' . $assnomid1 . '<br>
                <strong>Fecha y hora de inicio:</strong> ' . $fechainicio . '<br>
                <strong>Fecha y hora de finalización:</strong> ' . $fechafin . '<br>
                <strong>Sede:</strong> ' . $sede . '<br>
            </div><br><br>
            <div class="text">Firma y sello</div><br><br>';

            $pdf->SetFont('helvetica', '', 12);
            $pdf->writeHTML($html, true, false, true, false, '');

            $firmapath = $CFG->dirroot . '/local/recibeexamen/pix/firma.png';
            if (file_exists($firmapath)) {
                $pdf->Image($firmapath, 20, $pdf->GetY(), 50);
            }

            $pdf->Ln(40);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->MultiCell(0, 10, "Carretera de La Coruña, km 38,500 (vía de servicio, n.º 15) • 28400 Collado Villalba (Madrid) • 902 02 00 03\nwww.udima.es • informa@udima.es", 0, 'C');

            $filename = "justificante_{$user->username}.pdf";
            $tempdir = make_temp_directory('local_recibeexamen');
            $pdfpath = $tempdir . '/' . $filename;
            $pdf->Output($pdfpath, 'F');

            $subject = "Justificante - {$user->username}";
            $message_plain = "Estimado/a {$user->firstname},\n\nAdjunto le remitimos el justificante de asistencia al examen que se realizó en la fecha: " . $fechainicio . " en la sede: " . $sede . ".\n\nSaludos cordiales.";
            $message_html = nl2br($message_plain);

            $justificante_email = get_config('local_recibeexamen', 'justificante_email');
            $enable_studentsend = get_config('local_recibeexamen', 'enable_studentsend');

            $copiato = (object)[
                'id'                => -99,
                'email'             => $justificante_email,
                'firstname'         => 'Copia',
                'lastname'          => 'Justificantes',
                'username'          => 'justificante',
                'maildisplay'       => 1,
                'mailformat'        => 1,
                'emailstop'         => 0,
                'firstnamephonetic' => '',
                'lastnamephonetic'  => '',
                'middlename'        => '',
                'alternatename'     => '',
            ];

            email_to_user(
                $copiato,
                \core_user::get_support_user(),
                $subject . ' -  ' . $user->firstname . ' ' . $user->lastname . ' - ' . $params['asscodnum'],
                $message_plain,
                $message_html,
                $pdfpath,
                $filename
            );

            if ($enable_studentsend == 1) {
                email_to_user(
                    $user,
                    \core_user::get_support_user(),
                    $subject,
                    $message_plain,
                    $message_html,
                    $pdfpath,
                    $filename
                );
            }

            @unlink($pdfpath);

        } catch (\Exception $e) {
            debugging("Error en resend_task: " . $e->getMessage(), DEBUG_NORMAL);
        }
    }
}
