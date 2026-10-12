# Policonsultorio Medilink

Aplicación web en PHP para gestionar turnos, atención médica y auditoría de accesos en un policonsultorio.

## Requisitos

- XAMPP con Apache, PHP y MySQL.
- Extensión PDO MySQL habilitada en PHP.
- Una base de datos MySQL llamada `policonsultorio`.

## Ejecución local

1. Clona este repositorio dentro de `C:\xampp\htdocs\policonsultorio`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Abre <http://localhost/phpmyadmin/>.
4. En phpMyAdmin, selecciona **Importar**, elige `database/schema.sql` y ejecuta la importación. El script crea la base `policonsultorio`, las tablas y registros ficticios de prueba.
5. Verifica la conexión en `config/database.php` (configuración predeterminada de XAMPP: usuario `root`, contraseña vacía).
6. Abre <http://localhost/policonsultorio/>.

## Usuarios de demostración

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | `admin_demo` | `AdminDemo2026!` |
| Recepcionista | `recepcion_demo` | `RecepcionDemo2026!` |
| Médico | `medico_demo` | `MedicoDemo2026!` |

Estas cuentas y los datos asociados son solo para pruebas locales. Cambia o elimina las contraseñas antes de desplegar la aplicación o exponerla a Internet. No cargues datos reales de pacientes en este repositorio público.

## Módulos

- Inicio de sesión por roles.
- Panel de recepción y gestión de turnos.
- Panel médico e historias clínicas.
- Auditoría de accesos para administración.
