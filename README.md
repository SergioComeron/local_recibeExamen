# Local recibeexamen

Este es un plugin local para Moodle que permite recibir exámenes de manera eficiente.

## Requisitos

- Moodle 4.0.4 o superior
- PHP 7.4 o superior

## Instalación

1. Descargue el plugin desde el repositorio.
2. Extraiga el contenido en el directorio `local/local_recibeexamen` dentro de su instalación de Moodle.
3. Vaya a la página de administración de su sitio Moodle para completar la instalación.

## Uso

1. Navegue a la sección de administración del plugin.
2. Configure las opciones según sus necesidades.
3. Utilice la interfaz del plugin para recibir y gestionar exámenes.

## Llamada al Webservice

Para hacer una llamada al webservice, puede utilizar el siguiente comando `curl`:

```sh
curl -X POST 'http://localhost/stable_404/webservice/rest/server.php' \
-F 'wstoken=4b6e7709db06dc3397ad578fe34faf30' \
-F 'wsfunction=local_recibeexamen_receive_exam' \
-F 'moodlewsrestformat=json' \
-F 'idusuldap=s2' \
-F 'asscodnum=1427' \
-F 'vaccodnum=981' \
-F 'gaccodnum=22241' \
-F 'anyanyaca=2024-25' \
-F 'tcocodalf=FEB' \
-F 'planomid1=Plan de estudios ejemplo' \
-F 'assnomid1=Nombre de la asignatura ejemplo' \
-F 'fechainicio=2025-04-10T09:00:00+02:00' \
-F 'fechafin=2025-04-10T11:00:00+02:00' \
-F 'sede=Campus Madrid' \
-F 'exacodnum=12345' \
-F 'dniprs=12345678A' \
-F 'pdfdata=@lorem.pdf'
```

### Parámetros

- **idusuldap**: Nombre de usuario del estudiante.
- **asscodnum**: ID del curso.
- **vaccodnum**: ID del curso.
- **gaccodnum**: ID del curso.
- **anyanyaca**: Curso académico.
- **tcocodalf**: Convocatoria.
- **planomid1**: Plan de estudios.
- **assnomid1**: Nombre de la asignatura.
- **fechainicio**: Fecha de inicio del examen en formato ISO 8601 con zona horaria.
- **fechafin**: Fecha de fin del examen en formato ISO 8601 con zona horaria.
- **sede**: Sede del examen.
- **exacodnum**: ID del examen.
- **dniprs**: DNI del estudiante.
- **pdfdata**: Archivo PDF que contiene los datos del examen.

## Licencia

Este proyecto está licenciado bajo la Licencia MIT. Consulte el archivo LICENSE para más detalles.
