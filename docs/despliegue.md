# Despliegue de TraineeIQ

Render y Vercel despliegan la rama `main`, así que el proyecto debe estar fusionado allí antes de los pasos 2 y 3.

Orden: primero la base de datos, luego el backend y por último el frontend. Cada paso necesita un dato del anterior.

Los nombres de los botones pueden cambiar. Si un panel no coincide con esta guía, sigue la documentación oficial de ese servicio.

Nunca escribas claves en este archivo, en el código ni en un chat. Van solo en el panel de cada servicio.

## 1. Base de datos en Clever Cloud

1. Crea una cuenta en <https://www.clever-cloud.com>.
2. Crea un add-on de **MySQL** con el plan gratis, si sigue disponible.
3. Antes de seguir, revisa en la página del plan el **número máximo de conexiones** y el **tamaño máximo**.
4. Abre el add-on y copia sus datos de conexión: host, puerto, base, usuario y contraseña.

## 2. Backend en Render

1. Crea una cuenta en <https://render.com> y conecta el repositorio de GitHub.
2. Crea un **Web Service** con estos datos:

| Campo | Valor |
| --- | --- |
| Rama | `main` |
| Root Directory | `backend` |
| Runtime | Docker |
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
| `DB_HOST` | Host de Clever Cloud |
| `DB_PORT` | Puerto de Clever Cloud |
| `DB_DATABASE` | Base de Clever Cloud |
| `DB_USERNAME` | Usuario de Clever Cloud |
| `DB_PASSWORD` | Contraseña de Clever Cloud |
| `SESSION_DRIVER` | `array` |
| `CACHE_STORE` | `file` |
| `GEMINI_API_KEY` | Tu clave de Gemini |
| `GEMINI_MODEL` | El mismo modelo que usas en local |
| `GITHUB_TOKEN` | Tu token de GitHub, de solo lectura |

`SESSION_DRIVER` y `CACHE_STORE` evitan consultas a la base en cada petición: la API usa tokens, no sesiones.

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
2. Todas las semanas se generan y quedan disponibles.
3. Guarda un repositorio y lo evalúa.
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
