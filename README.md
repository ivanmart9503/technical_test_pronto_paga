# Prueba técnica Iván Martínez

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8.0 o superior
- Nginx o Apache como servidor web

## Instalación

1. Instalar las dependencias del proyecto:
```
composer install
```

2. Configurar la base de datos:

> IMPORTANTE: Se debe crear una base de datos en MySQL con el nombre `technical_test`, de preferencia usar el usuario root y sin contraseña para poder correr las migraciones.

Si se utiliza otro nombre para la base de datos, o se usa otro usuario y contraseña, se debe especificar en el archivo `.env` en las keys `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD` respectivamente

3. Ejecutar las migraciones:
```
php artisan migrate:fresh --seed
```

## Ejecución

El proyecto es una API Rest por lo que es necesario utilizar programa como Postman o similares para realizar las peticiones HTTP.

## Endpoints

- POST /api/auth/login: Autenticar al usuario (como paciente o doctor) y obtener el token de acceso
- GET /api/appointments: Obtener citas del usuario doctor autenticado
    - Se puede filtrar por rango de fechas enviando los parámetros `start_date` y `end_date` en el query string.
- GET /api/appointments/today: Obtener las citas del usuario doctor autenticado sólo para el día actual
- GET /api/appointments/{id}/pay: Obtener un link para pagar la cita como usuario paciente
- GET /api/appointments/{id}/payment-success: Este endpoint es llamado por el proveedor de pagos (Stripe) cuando el pago realizado por el paciente es exitoso
- GET /api/appointments/{id}/payment-failed: Este endpoint es llamado por el proveedor de pagos (Stripe) cuando el pago realizado por el paciente falla
- POST /api/appointments: Crear una nueva cita como usuario paciente
- PUT /api/appointments/{id}/confirm: Confirmar una cita como usuario doctor
- PUT /api/appointments/{id}/cancel: Cancelar una cita como usuario doctor

## Pruebas unitarias

Se pueden ejecutar las pruebas unitarias con el siguiente comando en la raíz del proyecto:

```
php artisan test
```

Los test están ubicados en la carpeta `tests` en la raíz del proyecto.

> NOTA: Al ejecutar los tests la info de la base de datos se vacía ya que se usa el trait `RefreshDatabase` que elimina y crea la base de datos en cada test. Se recomienda ejecutar las migraciones nuevamente para volver a tener la data de prueba.


## Instrucciones de uso (flujo del sistema)

1. Al correr las migraciones se genera data de prueba y se pueden usar los siguientes usuarios para iniciar sesión:
    - Paciente:
        - email: patient1@test.com
        - password: a123456
    - Doctor:
        - email: doctor1@test.com
        - password: a123456
2. Iniciar sesión como paciente y guardar el token de acceso que retorna el endpoint de login.
3. Iniciar sesión como doctor y guardar el token de acceso que retorna el endpoint de login.

### Paciente

> IMPORTANTE: Al hacer el llamado a los endpoints enviar el token de acceso en el header `Authorization` con el valor `Bearer {token}`, donde `{token}` es el token de acceso que se obtuvo en el paso 2.**

1. Usar el endpoint `/api/appointments` para crear una nueva cita con los siguientes datos en formato JSON:
    ```jsonc
    {
        // Id del doctor con el que se desea agendar la cita, para mayor facilidad puede ser el valor 1 ó 2 que corresponden a los id de losdoctores de prueba
        "doctor_id": 1,
        // Fecha y hora de la cita en formato `Y-m-d H:i`
        "date_time": "2025-05-25 10:00"
    }
    ```
    El endpoint retorna la cita creada.

2. Para generar un link de pago para la cita, usar el endpoint `/api/appointments/{id}/pay` con el id de la cita generado en el paso 1.

    El endpoint retorna un `payment_url` que se debe usar en el navegador para realizar el pago. Después de realizar el pago con éxito, el proveedor de pagos (Stripe) llama al endpoint `/api/appointments/{id}/payment-success` con el id de la cita para notificar al sistema que el pago fue exitoso y cambiar el estado de la cita a `paid`, **por eso es importante no cerrar el navegador hasta que se redireccione a este endpoint automáticamente.**

### Doctor

> IMPORTANTE: Al hacer el llamado a los endpoints enviar el token de acceso en el header `Authorization` con el valor `Bearer {token}`, donde `{token}` es el token de acceso que se obtuvo en el paso 2.

1. Usar el endpoint `/api/appointments` para obtener todas las citas.
    - Se puede filtrar por rango de fechas enviando los parámetros `start_date` y `end_date` en el query string.

2. Para mayor comodidad se puede usar el endpoint `/api/appointments/today` para obtener las citas del día actual.

3. Usar el endpoint `/api/appointments/{id}/confirm` con el id de la cita a confirmar.

    El estado de la cita cambiará a `confirmed` una vez confirmada la cita.

4. Usar el endpoint `/api/appointments/{id}/cancel` con el id de la cita a cancelar.
    - No se puede cancelar una cita que ya fue confirmada.
    - No se puede cancelar una cita que ya fue pagada.
    
    El estado de la cita cambiará a `cancelled` una vez cancelada la cita.
    
## Validaciones realizadas

1. Login:
    - El email y password deben ser requeridos.
    - El email debe ser un email válido.
    - El email debe existir en la base de datos.
    - El password debe coincidir con el hash almacenado en la base de datos.
2. Crear cita:
    - El usuario debe estar autenticado como paciente.
    - El doctor_id debe existir en la base de datos.
    - La date_time debe ser una fecha y hora válidas.
    - La cita debe ser para el día actual o en el futuro.
    - La cita debe ser para un horario en el que esté disponible el doctor.
    - La cita no debe solaparse con otras citas del mismo doctor.
3. Genera link de pago para la cita:
    - El usuario debe estar autenticado como paciente.
    - La cita pertenece al usuario autenticado.
    - La cita no ha sido cancelada.
    - La cita no ha sido pagada con anterioridad.
4. Confirmar cita:
    - El usuario debe estar autenticado como doctor.
    - La cita pertenece al usuario autenticado.
    - La cita no ha sido cancelada.
    - La cita no ha sido confirmada.
    - La cita ha sido pagada.
    - La cita aún no ha expirado.
5. Cancelar cita:
    - El usuario debe estar autenticado como doctor.
    - La cita pertenece al usuario autenticado.
    - La cita no ha sido cancelada.
    - La cita no ha sido confirmada.
    - La cita no ha sido pagada.
    - La cita aún no ha expirado.

## Notas adicionales

Adjunto una colección de **Postman** con los endpoints y ejemplos de uso.
El archivo es `Prueba técnica Iván Martínez.postman_collection.json`.

El token de acceso del paciente y doctor se pueden establecer en la
sección **variables** de las carpetas Patient y Doctor respectivamente. Con esto, no es necesario incluir el token en cada llamado a los endpoints.
