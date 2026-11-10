# Despliegue de TraineeIQ

Por ahora Render despliega `rama-ia` (desde el 2026-11-09), mientras se fusiona todo a `main`. Cuando esté fusionado, se cambia la rama siguiendo la sección 7.

Orden: primero la base de datos, luego el backend y por último el frontend. Cada paso necesita un dato del anterior.

Los nombres de los botones pueden cambiar. Si un panel no coincide con esta guía, sigue la documentación oficial de ese servicio.

Nunca escribas claves en este archivo, en el código ni en un chat. Van solo en el panel de cada servicio.

## 1. Base de datos en TiDB Cloud

TiDB es compatible con MySQL: Laravel lo usa con `DB_CONNECTION=mysql`. Se eligió en lugar de Clever Cloud porque el plan gratis de Clever Cloud solo da 10 MB y 5 conexiones. Las 169 pruebas pasaron contra TiDB Cloud el 2026-11-09.

**Ya está creada** (2026-11-09): instancia `traineeiq`, plan Starter, AWS N. Virginia (us-east-1), límite de gasto en $0. La base `traineeiq` existe vacía; sus tablas las crea el backend al arrancar.

Para crearla de nuevo:

1. Crea una cuenta en <https://tidbcloud.com>.
2. **Create Resource** → plan **Starter** (el formulario trae seleccionado Essential, que es de pago) → **MySQL Compatible**.
3. Región **N. Virginia (us-east-1)**, cerca de Render, y **Monthly Spending Limit en $0**. Con $0 nunca cobra: si se acaba el cupo gratis (5 GiB y 50 millones de Request Units al mes), la base se pone lenta en vez de cobrar.
4. Cuando el estado diga **Available**, pulsa **Connect** → **Generate Password**. TiDB muestra la contraseña una sola vez.
5. Copia el host, el puerto (4000) y el usuario, que lleva un prefijo propio de la cuenta (`xxxxxxxx.root`).
6. Crea la base `traineeiq`, por ejemplo desde el **SQL Editor** de TiDB: `CREATE DATABASE traineeiq;`

## 2. Backend en Render

1. Crea una cuenta en <https://render.com> y conecta el repositorio de GitHub.
2. Crea un **Web Service** con estos datos:

| Campo | Valor |
| --- | --- |
| Rama | `rama-ia` (temporal; luego `main`) |
| Root Directory | `backend` |
| Runtime | Docker |
| Región | Virginia (US East), la misma de la base |
| Plan | Free |
| Health Check Path | `/up` |

3. Genera la clave de la aplicación en tu computador, desde `backend/`. Este comando la muestra sin guardarla:

```bash
php artisan key:generate --show
```

4. Agrega estas variables de entorno en el panel de Render:

| Variable | Valor |
| --- | --- |
| `APP_NAME` | `TraineeIQ` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | La clave del paso 3 |
| `APP_URL` | La URL que Render le dé al servicio |
| `FRONTEND_URL` | La URL de Vercel, sin `/` al final. Se completa en el paso 3 de esta guía |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Host de TiDB Cloud |
| `DB_PORT` | `4000` |
| `DB_DATABASE` | `traineeiq` |
| `DB_USERNAME` | Usuario de TiDB Cloud, con su prefijo |
| `DB_PASSWORD` | Contraseña de TiDB Cloud |
| `MYSQL_ATTR_SSL_CA` | `/etc/ssl/certs/ca-certificates.crt` |
| `SESSION_DRIVER` | `array` |
| `CACHE_STORE` | `file` |
| `GEMINI_API_KEY` | Tu clave de Gemini |
| `GEMINI_MODEL` | El mismo modelo que usas en local |
| `GITHUB_TOKEN` | Tu token de GitHub, de solo lectura |

`SESSION_DRIVER` y `CACHE_STORE` evitan consultas a la base en cada petición: la API usa tokens, no sesiones.

`MYSQL_ATTR_SSL_CA` activa la conexión cifrada que exige TiDB Cloud. No es un secreto: es la ruta de los certificados dentro de la imagen `php:8.3-apache`, comprobada el 2026-11-09.

Los límites de evaluación tienen valores por defecto y no hace falta definirlos. Si quieres cambiarlos, están en `backend/.env.example`.

5. Despliega. Al arrancar, el servidor corre las migraciones por sí solo.
6. Abre `https://TU-SERVICIO.onrender.com/up`. Debe responder una página de estado.

No corras `migrate:fresh` ni `--seed` en producción: lo primero borra los datos y lo segundo crea usuarios con contraseñas conocidas.

## 3. Frontend en Vercel

1. Crea una cuenta en <https://vercel.com> y conecta el repositorio.
2. Crea un proyecto con estos datos:

| Campo | Valor |
| --- | --- |
| Rama de producción | `main` |
| Root Directory | `frontend` |
| Framework | Vite |

3. Agrega la variable `VITE_API_URL` con `https://TU-SERVICIO.onrender.com/api`.
4. Despliega y copia la URL final.
5. Vuelve a Render y pon esa URL en `FRONTEND_URL`. El servicio se reinicia solo.

## 4. Prueba de producción

Registra usuarios nuevos desde la pantalla de registro y recorre estos flujos.

**Flujo de grupo**
1. Un instructor crea un grupo, porque por ahora no hay pantalla: se hace con Postman en `POST /api/grupos`.
2. Un estudiante entra con el código.
3. El instructor sube un PDF y espera a que las semanas queden listas en `/prueba/guias`.
4. El instructor libera una semana y el estudiante la ve.
5. El estudiante guarda un repositorio y lo evalúa.
6. El instructor abre "Ver entregas del grupo".

**Flujo personal**
1. Un estudiante sube un PDF de varias semanas.
2. Todas las semanas se generan, pero solo la 1 queda abierta.
3. Guarda un repositorio, lo evalúa y ve subir su experiencia. La semana 2 se abre con el 80 % del XP de la semana 1.
4. Otro usuario no puede abrir esa guía.

**Límites por medir**
- Subir un PDF cercano a 10 MB, porque Render gratis tiene 512 MB de RAM.
- Cuánto tarda una evaluación real desde Render.
- Dos evaluaciones al mismo tiempo.
- Recargar una ruta como `/prueba/guias` en Vercel.
- Cinco logins fallidos de un usuario no deben bloquear a otro.

## 5. Límites conocidos

- Render apaga el servicio tras 15 minutos sin tráfico y tarda cerca de un minuto en volver. Abre la app antes de una demostración.
- Los PDF subidos pueden perderse al reiniciar. No afecta a la app, porque su texto queda en la base y el archivo no se vuelve a leer.
- Apache atiende 6 peticiones a la vez. Si 6 personas evalúan al mismo tiempo, las demás esperan.
- TiDB Cloud gratis da 5 GiB y 50 millones de Request Units al mes. Si se acaban, con el límite de gasto en $0 la base se pone lenta hasta el mes siguiente; no cobra.
- TiDB es compatible con MySQL, pero no idéntico: los ids no siempre son consecutivos. El código no depende de eso.

## 6. Cambios en la base de datos

**Cómo se actualiza:** con cada push a la rama que despliega Render, el servidor arranca y corre `php artisan migrate --force` (`backend/docker/iniciar.sh`). Laravel anota en la tabla `migrations` de TiDB las que ya corrieron y solo aplica los archivos nuevos. Si una migración falla, el servidor no arranca.

**Regla:** producción ya tiene datos, así que nunca se edita una migración vieja. Laravel no la vuelve a correr y el cambio no llegaría a TiDB. Todo cambio va en una migración nueva (`php artisan make:migration ...`), como se hizo con `2026_11_09_120000_create_notificaciones_table`.

**En local:** `php artisan migrate` aplica solo lo nuevo. `php artisan migrate:fresh --seed` borra todo: solo en local, nunca apuntando a TiDB.

## 7. Cambiar de `rama-ia` a `main`

1. Fusionar `rama-ia` a `main` con un Pull Request completo, para que lleguen las migraciones, el `Dockerfile` y `docker/`.
2. No fusionar `rama-database`: crea las tablas otra vez con otros nombres de archivo y el servidor no arrancaría.
3. Verificar desde `backend/` que las migraciones sean idénticas. Este comando no debe mostrar nada:

```bash
git fetch origin
git diff origin/rama-ia origin/main -- database/migrations
```

4. Render: Settings → Build & Deploy → Branch → `main`. Vercel: Production Branch → `main`. No se pierden datos ni variables.
5. Comprobar que `/up` responde 200 y que el log del deploy diga `Nothing to migrate`, o liste solo migraciones nuevas.

Después del cambio, cada fusión a `main` despliega sola; los push a `rama-ia` ya no llegan a producción.
