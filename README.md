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
-F 'pdfdata=@lorem.pdf'
```

## Contribuir

Si desea contribuir a este proyecto, por favor siga estos pasos:

1. Haga un fork del repositorio.
2. Cree una nueva rama (`git checkout -b feature/nueva-funcionalidad`).
3. Realice sus cambios y haga commit (`git commit -am 'Añadir nueva funcionalidad'`).
4. Haga push a la rama (`git push origin feature/nueva-funcionalidad`).
5. Cree un nuevo Pull Request.

## Licencia

Este proyecto está licenciado bajo la Licencia MIT. Consulte el archivo LICENSE para más detalles.

## Contacto

Para cualquier consulta, puede contactar con el mantenedor del proyecto en [correo@example.com](mailto:correo@example.com).