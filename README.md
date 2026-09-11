# Pagos App

Aplicación PHP para controlar tickets, empleados, sedes, jornadas, gastos, pagos y liquidaciones.

## Funcionalidades

- Registro de tickets por empleado y sede.
- Conversión de monedas mediante tasas configurables.
- Gastos semanales y mensuales.
- Reportes de ingresos, ganancias y pagos.
- Cierre de jornada y consulta histórica.
- Administración de empleados, usuarios, roles y sedes.
- Exportación de información para análisis.

## Tecnologías

- PHP y Apache.
- MySQL/MariaDB con `mysqli`.
- Composer y Docker Compose.

## Inicio local

1. Copia `.env.example` como `.env`.
2. Aplica `app/database/schema.sql` en una base MySQL.
3. Instala dependencias de Composer cuando corresponda.
4. Ejecuta `docker compose up --build`.

El esquema no contiene empleados, tickets, pagos ni gastos reales.
