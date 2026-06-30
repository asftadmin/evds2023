## Consulta gerencial permisos por mes

Estado: implementado.

- Crear vista `view/MntInboxT/consulta_permisos_mes.php`.
- Crear JS `view/MntInboxT/consulta_permisos_mes.js`.
- Crear `controller/informes.php` para endpoints JSON limpios.
- Extender `models/Informes.php` con el SQL de colaboradores y permisos mensuales.
- Consultar BioTime unicamente mediante `controller/curl.php`.
- Agregar acceso en `view/MntInboxT/carpetas.php`.
- Mostrar resumen por cards y detalle en DataTable ordenado por fecha descendente.
