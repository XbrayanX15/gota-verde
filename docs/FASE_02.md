# GotaVerde: Fase 02 (sistema completo)

Este paquete trae **solo los archivos nuevos o modificados** respecto al repositorio `master-chef552/gota-verde` (commit `f54cffb`). Se copia encima del proyecto igual que la Fase 01. **No se agregan dependencias nuevas** de Composer ni de npm.

## 1. Qué se implementa

| Módulo | Requerimientos / casos de uso | Dónde está |
|---|---|---|
| Recuperación de contraseña por correo | RF-03, CU-03 | `Auth/RecuperarContrasenaController`, vistas `auth/recuperar` y `auth/restablecer` |
| Bloqueo tras 5 intentos fallidos (60 s) | CU-02 4b | `Auth/SesionController` |
| Rol global usuario/admin y panel de administración | RF-04, CU-04 | `users.rol`, middleware `admin`, `Admin/UsuarioController` |
| Catálogo de actividades y logros (admin) | CU-16 | `Admin/ActividadController`, `Admin/LogroController` |
| Roles por vivienda con Policy | RF-05, RNF-31 | `Policies/ViviendaPolicy` |
| Editar y eliminar vivienda, tarifa sugerida | RF-15, CU-05 2a, CU-06 | `ViviendaController` |
| Invitaciones con token de 72 h por correo (cola) | RF-20, CU-13, CU-14, DS-05 | `InvitacionesService`, `InvitacionController`, `Notifications/InvitacionAVivienda` |
| Eliminar miembros / salir de la vivienda | RF-22, CU-15 | `MiembroController` |
| Lecturas y recibos por vivienda, validación de traslape y comparación con el periodo anterior en litros y pesos | RF-06, RF-13, RF-17, CU-08, DS-03 | `LecturaController`, vista `lecturas/index` |
| Hábitos: más actividades y aviso de duplicado (sumar o separar) | RF-07, RF-21, CU-09 5a | `RegistroHabitoController` |
| Evento `HabitoRegistrado` → gamificación | DS-02 | `Events/HabitoRegistrado`, `Listeners/ProcesarGamificacion` |
| Rachas, logros, puntos, niveles y recompensas | RF-09, RF-10, RF-11, CU-11, CU-18 | `GamificacionService`, vista `logros/index` |
| Metas cuantitativas y cualitativas por vivienda | RF-08, RF-18, RF-21, CU-10, DS-04 | `MetasService`, `MetaController` |
| Dashboard individual y global (aporte por vivienda y tendencia mensual), con caché | RF-12, RF-13, RF-19, DS-06 | `DashboardService`, vistas `dashboard/*` |
| Cierre diario programado | CU-19 | `Console/Commands/CierreDiario`, `routes/console.php` |
| Mensajes de validación en español | — | `lang/es/*` |
| 32 pruebas nuevas (40 en total) | — | `tests/Feature/Fase02/*` |

## 2. Antes de copiar: arreglar el repositorio (importante)

El `.gitignore` del repo estaba **vacío**, así que se subieron a GitHub `.env` (con la contraseña de MySQL y el `APP_KEY`), `vendor/`, `node_modules/` y `public/hot`. Este paquete incluye un `.gitignore` estándar de Laravel. Después de copiarlo, ejecuten **una sola vez**:

```powershell
git rm -r --cached .env vendor node_modules public/hot
git commit -m "Quitar archivos privados y dependencias del repositorio"
```

Después:

1. **Cambien la contraseña del usuario de MySQL**, porque la actual quedó pública en el historial de GitHub.
2. Generen un `APP_KEY` nuevo con `php artisan key:generate`. Se cerrarán las sesiones abiertas.
3. Borren `public/hot` si existe. Ese archivo hace que Laravel busque el servidor de Vite y la página se vea sin estilos cuando no está corriendo `npm run dev`.

## 3. Copiar los archivos

1. Descomprime `GotaVerde_Fase_02.zip`.
2. Copia el **contenido** de `gotaverde_fase_02` en la carpeta del proyecto y acepta reemplazar los archivos.
3. **Borra la carpeta `resources/views/pendientes/`**, porque sus maquetas se reemplazaron por las pantallas reales.
4. En tu `.env` (no se incluye en el ZIP) cambia o agrega:

```dotenv
APP_NAME=GotaVerde
APP_LOCALE=es
SESSION_LIFETIME=30
MAIL_MAILER=log
QUEUE_CONNECTION=database
```

`SESSION_LIFETIME=30` cierra la sesión tras 30 minutos de inactividad (RNF-32). Con `MAIL_MAILER=log`, los correos de recuperación e invitación se escriben en `storage/logs/laravel.log` en lugar de enviarse.

## 4. Migrar y cargar catálogos

```powershell
php artisan config:clear
php artisan migrate
php artisan db:seed
```

- `migrate` agrega `rol` y `puntos` a `users` y crea `invitaciones`, `lecturas_consumo`, `metas`, `rachas`, `logros`, `logro_usuario`, `recompensas` y `recompensa_usuario`. No borra datos.
- `db:seed` carga 5 actividades, 10 logros y 4 recompensas. Usa `firstOrCreate`, así que puedes ejecutarlo varias veces sin duplicar nada. En `local` también crea `test@example.com` / `password`.
- **No uses `migrate:fresh`** en tu base habitual.

Crea el primer administrador con una cuenta ya registrada:

```powershell
php artisan gotaverde:admin tu_correo@ejemplo.com
```

## 5. Ejecutar

```powershell
npm run build
composer run dev
```

`composer run dev` levanta el servidor, Vite y `queue:listen`, que envía los correos de la cola.

**Tareas programadas (cierre diario):**
- Localmente: `php artisan gotaverde:cierre-diario` para ejecutarlo a mano, o `php artisan schedule:work` para dejar el planificador corriendo.
- En el servidor Linux: `* * * * * cd /ruta/gotaverde && php artisan schedule:run >> /dev/null 2>&1`.

## 6. Pruebas automáticas

```powershell
php artisan test
```

`phpunit.xml` ya fuerza SQLite en memoria, así que las pruebas **no tocan tu base de MySQL**. Resultado esperado: **40 pruebas pasando**.

## 7. Prueba manual sugerida

1. **Recuperar contraseña:** en `/ingresar` abre "¿Olvidaste tu contraseña?", envía tu correo y copia el enlace de `storage/logs/laravel.log`.
2. **Bloqueo:** equivócate 5 veces de contraseña. El sexto intento debe decir "Intenta de nuevo en N segundos".
3. **Invitación:** en *Mis viviendas* invita a otro correo, copia el enlace que aparece, ábrelo en una ventana privada, crea la cuenta con ese correo y acepta.
4. **Permisos:** con la cuenta invitada (miembro) intenta abrir `/viviendas/{id}/editar`. Debe responder 403.
5. **Hábitos:** registra una ducha de 3 min. Deben aparecer el aviso de racha y el logro "Primera gota". Registra otra ducha el mismo día para ver la pregunta de sumar o separar.
6. **Metas:** crea una meta cuantitativa de 50 L y registra duchas cortas desde ambas cuentas hasta cumplirla.
7. **Lecturas:** registra dos recibos consecutivos y revisa la comparación. Intenta registrar un periodo traslapado.
8. **Dashboard global:** crea una segunda vivienda con otra tarifa y cambia a la pestaña *Global*.
9. **Admin:** como administrador, cambia los litros de una actividad y desactiva otra. La desactivada ya no aparece al registrar hábitos.
10. **Cierre diario:** ejecuta `php artisan gotaverde:cierre-diario`.

## 8. Reglas de negocio implementadas (para el documento)

- **Ahorro por hábito:** `max(0, referencia_eficiente − cantidad) × litros_por_unidad`. Los valores del catálogo son **referencias editables, no mediciones**, y deben justificarse en el documento.
- **Pesos:** `litros / 1000 × tarifa_m3` de cada vivienda.
- **Racha:** días consecutivos en que el usuario registra al menos un hábito en esa vivienda. Si no registra ayer ni hoy, vuelve a 0. Guarda la mejor racha.
- **Puntos:** 10 por hábito más los puntos de cada logro. Nivel = `puntos / 100 + 1`. Una recompensa se otorga al alcanzar sus puntos.
- **Meta cuantitativa:** litros ahorrados por **todos los miembros** en el periodo, opcionalmente de una sola actividad.
- **Meta cualitativa:** días en que **todos** los registros de la actividad respetaron el máximo (por ejemplo, duchas de 5 min o menos) contra los días objetivo.
- **Comparación de lecturas:** se compara el **promedio diario** de cada periodo, porque los bimestres no duran lo mismo.
- **Invitaciones:** el token se genera aleatorio (64 caracteres) y en la base solo se guarda su **hash SHA-256**. Vence en 72 h y solo la acepta una cuenta con el mismo correo.
- **RF-08 "tener consumo base":** se muestra como recomendación en la pantalla de metas, pero no bloquea la creación.
- **Gráficas:** se mantuvieron las barras CSS de la Fase 01 en lugar de Chart.js, para no agregar dependencias. Si el profesor exige Chart.js, se puede cambiar sin tocar los servicios.

## 9. Guardar en GitHub

```powershell
git checkout -b fase-02
git add .
git status   # verifica que NO aparezcan .env, vendor ni node_modules
git commit -m "Fase 02: módulos completos de GotaVerde"
git push -u origin fase-02
```

Después abran un Pull Request hacia `main` para que tu compañero lo revise.
