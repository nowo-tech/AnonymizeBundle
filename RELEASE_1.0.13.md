# Release 1.0.13 (2026-02-21)

## Resumen

Release de mantenimiento: corrección para servicios de anonymización personalizados (obligatorio `public: true`) y mejoras en los demos (Docker, PostgreSQL).

## Cambios principales

### Para aplicaciones que usan `anonymizeService`

- **Servicio público**: Si usas `#[Anonymize(anonymizeService: TuServicio::class)]`, el bundle resuelve el servicio por id. En Symfony los servicios son privados por defecto y se inlinan al compilar. **Debes declarar tu servicio de anonymización como `public: true`** en `config/services.yaml` para evitar el error "The service or alias has been removed or inlined". Ver demos: `App\Service\SmsNotificationAnonymizerService` con `public: true` en symfony6/7/8.

### Demos (symfony6, symfony7, symfony8)

- **make install / make up**: Se usa `docker-compose run --rm php composer install` para que la instalación funcione aunque el contenedor PHP haya salido (p. ej. por falta de `vendor/`). Luego se levanta el servicio PHP con `docker-compose up -d php`.
- **SmsNotificationAnonymizerService**: Declarado `public: true` en los tres demos para que el comando de anonymización pueda resolverlo.
- **PostgreSQL init**: La tabla `users` en `demo/docker/init/postgres/init.sql` usa columnas `first_name`, `last_name`, `credit_card` (en lugar de `firstname`, etc.) para coincidir con la naming strategy de Doctrine. Quien ya tenga volumen de Postgres debe borrarlo (p. ej. `rm -rf demo/symfony8/.data/postgres`) y volver a ejecutar `make setup`.

## Documentación actualizada

- **CHANGELOG.md**: Entrada [1.0.13] - 2026-02-21.
- **UPGRADING.md**: Sección "Upgrading to 1.0.13" con pasos de migración.
- **USAGE.md**: Nota en la sección de anonymizeService sobre la necesidad de `public: true`.

## Comandos para publicar el tag

```bash
# Desde la raíz del repo (AnonymizeBundle)
git add docs/CHANGELOG.md docs/UPGRADING.md docs/USAGE.md RELEASE_1.0.13.md
git status   # revisar que solo estén los archivos deseados
git commit -m "Release 1.0.13: custom anonymizer public service, demo Docker/Postgres fixes"
git tag -a v1.0.13 -m "Release 1.0.13

- Custom anonymizer: require public: true when using anonymizeService by FQCN
- Demo: make install/up use docker-compose run for composer install
- Demo: SmsNotificationAnonymizerService public in services.yaml (symfony6/7/8)
- Demo: Postgres init users table columns first_name, last_name, credit_card
- Docs: CHANGELOG, UPGRADING, USAGE updated"
git push origin main
git push origin v1.0.13
```

Después del push, crear el release en GitHub desde la tag `v1.0.13` y pegar el contenido de esta nota (o el CHANGELOG) en la descripción.
