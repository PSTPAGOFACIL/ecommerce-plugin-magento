PST Pago Fácil SpA  Magento 2
============================================================

## Descripción ##

Pago Fácil, soporta Webpay de Transbank, Khipu , Multicaja, Mach y Pago46.

## Instalación ##

Descargar desde el repositorio el plugin , descomprimir y renombrar el plugin a PagoFacilChile.

Copiar el plugin dentro de la carpeta

{Mangento Dir}/app/code


## Ejecutar los siguientes comandos ##

```bash

php bin/magento setup:upgrade
php bin/magento module:enable PagoFacil_PagoFacilChile --clear-static-content
php bin/magento setup:di:compile

```

## Configuración ##

-Para configurar ir Stores > Configuration > Sales > Payment methods > PST Pago fácil SPA version 1.1.0
-Agregar el Service Token y el Secret Token, que se obtienen en el dashboard de Pago Fácil (correspondiente al ambiente seleccionado)

