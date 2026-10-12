# Policonsultorio Medilink

Aplicación web en PHP para gestionar turnos, atención médica y auditoría de accesos en un policonsultorio.

## Requisitos

- XAMPP con Apache, PHP y MySQL.
- Extensión PDO MySQL habilitada en PHP.
- Una base de datos MySQL llamada `policonsultorio`.

## Ejecución local

1. Clona este repositorio dentro de `C:\xampp\htdocs\policonsultorio`.
2. Inicia Apache y MySQL desde el panel de XAMPP.
3. Configura la conexión a MySQL en `config/database.php`.
4. Crea la base `policonsultorio` e importa el esquema de tablas requerido por la aplicación.
5. Abre <http://localhost/policonsultorio/>.

> El esquema SQL de la base de datos no está incluido todavía en este repositorio.

## Módulos

- Inicio de sesión por roles.
- Panel de recepción y gestión de turnos.
- Panel médico e historias clínicas.
- Auditoría de accesos para administración.
