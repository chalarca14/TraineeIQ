[33mcommit b93cf0f3674afda110acc07d8078a36ce8695bac[m[33m ([m[1;36mHEAD[m[33m -> [m[1;32mrama-frontend[m[33m, [m[1;31morigin/rama-frontend[m[33m)[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Sun Oct 25 19:43:56 2026 -0500

    Arreglo del registro

[33mcommit 46eda6f1c69a21414def14776105432ffa64d73f[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Sun Oct 25 19:11:43 2026 -0500

    Cambios temporales

[33mcommit f9ddc8f6ccb2cb67a939fcb72c2a1e37b5ede37d[m[33m ([m[1;31morigin/rama-prueba[m[33m, [m[1;32mrama-prueba[m[33m)[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Sat Oct 24 18:03:11 2026 -0500

    fix: agregar columnas faltantes y permitir grupo_id null en guias
    
    - guias.grupo_id ahora es nullable para las guias personales
    
    - recomendaciones: agrega tema_id (FK a guia_temas)
    
    - mini_proyectos: agrega guia_id (FK a guias) y contenido
    
    Requiere ejecutar: php artisan migrate

[33mcommit ba38b4aea990abcd063c63141de6b69bbcb56baf[m
Merge: d4f0432 dd934f0
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Fri Oct 23 14:48:21 2026 -0500

    Ramas mergeadas y conflicto de Dashboard.vue Instructor solucionado

[33mcommit d4f04320d09402368a32ab7d2e97c51f05459ac1[m
Merge: 87b19fa 1b0e5c1
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Fri Oct 23 14:39:47 2026 -0500

    Merge remote-tracking branch 'origin/rama-ia' into rama-prueba

[33mcommit 87b19fa942f2e89bfab94ce6166b419281e0f9dd[m
Merge: d24367c 3cc9272
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Fri Oct 23 14:35:09 2026 -0500

    Merge remote-tracking branch 'origin/rama-database-dev' into rama-prueba
    git merge origin/rama-database-dev#

[33mcommit dd934f0efc072291870cafa6833fe9377bfdb6bf[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Fri Oct 23 14:28:18 2026 -0500

    Prueba de cambios

[33mcommit d0adb75fc59eea0f9a7e4d4baab2b6379f134002[m
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Fri Oct 23 10:33:28 2026 -0500

    feat: implementar pruebas de  vistas de modo personal, vistas de instructor y corregir servicios

[33mcommit 3cc9272cacf8516d9660aba0cbe74d629704bfe0[m[33m ([m[1;31morigin/rama-database-dev[m[33m)[m
Merge: b2d57e6 c35da4e
Author: Sofia <sofia67yyy@gmail.com>
Date:   Fri Oct 23 09:21:54 2026 -0500

    Merge branch 'rama-prueba' into rama-database-dev

[33mcommit c35da4ec4cbce0beb31ee2dc7f493fd97b207315[m
Author: Sofia <sofia67yyy@gmail.com>
Date:   Fri Oct 23 09:06:03 2026 -0500

    feat: configuracion de migraciones, servicios de IA y controlador de guias

[33mcommit 1b0e5c145fdc4c01a54718760032b44fc5c77177[m
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Wed Oct 21 22:15:40 2026 -0500

    Actualizar modelos por defecto de Gemini

[33mcommit a10e0d92281d1d7bfb10dab9a3c9ea39c6c91491[m
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Wed Oct 21 21:59:51 2026 -0500

    Validar nivel y dificultad devueltos por Gemini

[33mcommit 4fecc673a54c3125ed4cc1934a997a45adbe06e7[m
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Wed Oct 21 21:59:51 2026 -0500

    Restaurar rutas de IA y enrutar el chat a ChatIaController

[33mcommit 259c80fc8ab2e1f577ad2bcdc733054cbf387525[m
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Tue Oct 20 22:18:30 2026 -0500

    Agregar servicio de chat con IA y vista de prueba desde rama-prueba

[33mcommit 9c1943b32fffb04b5e92fdac7cbe8b18547b205a[m
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Tue Oct 20 14:47:41 2026 -0500

    Actulizar .gitignore

[33mcommit 8ef0f3396b503ad95ef64fe649f288c41874d302[m
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Tue Oct 20 07:16:15 2026 -0500

    Ignore vscode directory

[33mcommit c9eecb24fb3afcd8dc4a68753109c42cc1e98d21[m
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Tue Oct 20 07:11:53 2026 -0500

    Agregar Dashboard del estudiante con banner y tarjetas de estadisticas

[33mcommit 9d7b6f15840935fac1ffcdac8c5502ce26968bf3[m
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Tue Oct 20 07:11:32 2026 -0500

    Agregar layout autenticado con NavBar, SideBar y switch de modo

[33mcommit 99a1a50fd067fae4c68afa590616bdb5d08ac85f[m
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Mon Oct 19 19:04:39 2026 -0500

    Agregar lectura de PDF y division de guias en temas con Gemini

[33mcommit d24367c65188ff1872487590b20e4abe58368c98[m
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Mon Oct 19 17:55:42 2026 -0500

    Usar request user en lugar de auth helper en RecomendacionController

[33mcommit b2d57e6f4d863fbaf724124ed5262032459547c9[m
Author: Sofia <sofia67yyy@gmail.com>
Date:   Sat Oct 17 20:16:02 2026 -0500

    fix: corregir lógica de validación y respuesta en el controlador de registro

[33mcommit 9beec2c26c2af67df6b025b35581d05562351462[m
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Fri Oct 16 08:13:02 2026 -0500

    fix: solucionar integración y funcionamiento de Gemini AI

[33mcommit 0411ba09408075561cc58fd0ab9e7698f1608f62[m
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Thu Oct 15 10:42:39 2026 -0500

    test: corregir errores y continuar pruebas de conexión con la IA

[33mcommit e97a202fa8bb5124772c15e663637cbac94f3e92[m
Merge: d61d47e fd935e9
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Wed Oct 14 10:03:10 2026 -0500

    Fix: resuelvo conflicto en api.php al fusionar rama-database-dev

[33mcommit d61d47eb37471af473d555096b12f7d27781dc5d[m
Merge: 699aff7 26b61cb
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Tue Oct 13 11:21:16 2026 -0500

    Fix: resuelve conflicto en el modelo User entre rama-prueba y rama-database-dev

[33mcommit 699aff7110bcdfd5d9763398d562b23d1d56aedf[m
Merge: 0547015 2cb6b9a
Author: santiago sanchez chalarca <santiagoschalarca@gmail.com>
Date:   Tue Oct 13 10:55:43 2026 -0500

    Fix: resuelve conflictos en vistas de auth y estudiante para cerrar el merge

[33mcommit fd935e9a927577b385a69d0aa14d25587f2fc2d1[m
Author: Sofia <sofia67yyy@gmail.com>
Date:   Tue Oct 13 09:54:50 2026 -0500

    feat: implementar controladores y rutas de grupos, guías y mini-proyectos para TraineeIQ

[33mcommit 26b61cbd3c632ad80c6066916d417f22b73dfad1[m
Author: Sofia <sofia67yyy@gmail.com>
Date:   Fri Oct 9 21:48:41 2026 -0500

    Implementar y estructurar todos los modelos Eloquent y relaciones

[33mcommit 0547015687a4ceebd4d59d07ced067d628396c13[m
Merge: 241b114 06a8057
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Fri Oct 9 10:57:51 2026 -0500

    Merge remote-tracking branch 'origin/rama-auth' into rama-prueba

[33mcommit 241b114106f0de6ac464d6fa36c20a3ec3e264bf[m
Author: jeffersonblandon <jefersonblandon47@gmail.com>
Date:   Thu Oct 8 11:22:58 2026 -0500

    Cambios temporales

[33mcommit 87d01bc162dec30b64080a15cc6c857bcad68645[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Wed Sep 16 14:58:02 2026 -0500

    Eliminar SocialButton, no contemplado en los requerimientos

[33mcommit 03892e39c2399278a98569e64dcd9a28d4178587[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Wed Sep 16 14:55:46 2026 -0500

    Conectar Registro con el backend mediante authService.registro()

[33mcommit a06559a29bcc3924870b9ab9406e2866b3907f7b[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Wed Sep 16 14:55:19 2026 -0500

    Agregar RegistroInfo con contenido dinamico segun el rol y animaciones

[33mcommit 2d578882873dc6671cb0137032dc5d10b91d2dd4[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Wed Sep 16 14:54:52 2026 -0500

    Reconstruir Registro desde cero: estructura, formulario y selector de rol

[33mcommit 8d846041c7902343d41cc6dd3d3cbbf19dcc7609[m
Author: Ana Rivera <asrivera886@gmail.com>
Date:   Wed Sep 16 14:54:08 2026 -0500

    Agregar animacion ripple reutilizable a BaseButton

[33mcommit 398edcb280024984c456092744c99bf1032519a5[m
Author: Ana Rivera <asrivera886@gmail.com>
Date: 