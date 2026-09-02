# Modificaciones de este fork

Fork de [DenverCoder1/readme-typing-svg](https://github.com/DenverCoder1/readme-typing-svg) (MIT).
El `LICENSE` original se conserva intacto. Este archivo documenta **solo lo que cambiamos**.

Se usa para la cabecera del perfil [Neo236](https://github.com/Neo236). Desplegado en el
proyecto de Vercel **`typing-aguara`**, que está enlazado a este repo: cada push a `main`
despliega solo.

> El proyecto viejo `readme-typing-svg` en Vercel **no** estaba enlazado (`link: null`), se
> desplegaba subiendo archivos a mano. Por eso durante un tiempo los cambios pusheados no
> aparecían nunca. Sigue en pie por si hay que volver.

## Infraestructura

| Qué | Dónde | Por qué |
|---|---|---|
| Punto de entrada en `api/` + autoload por `__DIR__` | `api/index.php`, `vercel.json` | El runtime PHP de Vercel exige que las funciones vivan en `api/`, y el `require` relativo al directorio de trabajo falla dentro de la lambda |
| Sin `lines`, redirige a este repo en vez de a la demo del upstream | `src/controllers/` | — |

## Parámetros nuevos

| Parámetro | Qué hace |
|---|---|
| `padding` | Margen interno de la caja |
| `borderColor` | Color del marco |
| `borderRadius` | Radio del marco |
| `restPause` | Pausa en vacío entre una frase y la siguiente |

El fondo pasó de `style=background-color` en la raíz a un `<rect>` propio, que es lo que
permite el radio y el marco.

## Animación

Todo esto vive en `src/templates/main.php`.

| Qué | Por qué |
|---|---|
| **Tiempos proporcionales al largo** | `duration` es el tiempo de la línea **más larga**; el resto escala, así la velocidad por carácter es constante. Las pausas (`pause`, `restPause`) son fijas y no dependen del largo |
| **El path crece solo hasta el ancho del texto** | Antes crecía siempre hasta el ancho útil sin importar cuánto medía la frase, así que una línea corta quedaba completa en el primer 22 % de la animación y después se quedaba quieta: se "escribía" a 76 caracteres por segundo en vez de a 17 |
| **Sin `x` en el `<text>` del textPath** | Cuando el texto va sobre un `<textPath>`, el `x` del `<text>` **no lo posiciona: se suma** como desplazamiento sobre el path. Como el path ya arranca en `m$padding`, el padding se contaba dos veces: el texto empezaba 20 px a la derecha del cursor y los últimos 20 px (dos caracteres) se salían del path y no se dibujaban |
| **Pasos de un carácter (`calcMode='discrete'`)** | Una consola no desliza el texto: aparece una celda entera por vez. Además elimina el solape: un glifo se dibuja cuando su punto medio entra en el path, así que con interpolación continua el texto podía terminar hasta media celda más allá del cursor |
| **Cursor como elemento propio** | Antes era un `<tspan>` al final del textPath, así que solo aparecía cuando el path ya cubría toda la línea: durante el tipeo no había cursor y era el primero en cortarse. Ahora vive en el punto de inserción y comparte `keyTimes` con el path, así que no se puede desincronizar |
| **Sólido al escribir, parpadea al esperar** | Como una terminal de verdad. El parpadeo pasó de CSS a SMIL para poder cortarlo por fase; la clase `.cursor` quedó solo para el cursor pegado de `center=true` y multilínea |
| **`prefers-reduced-motion`** | Es una animación en bucle infinito sin forma de pausarla, que es el caso de WCAG 2.2.2, y una imagen servida por camo no puede llevar controles. CSS no puede detener SMIL, pero sí esconderlo: se ocultan `.anim` y aparece un `<text class='fijo'>` con la primera frase |

## Ojo con esto

**`AVANCE_EM = 0.6`.** El ancho del path y los saltos del cursor se calculan asumiendo que
la fuente es monoespaciada con avance de 0.6 em — verificado sobre el `hmtx` de Fira Code,
donde **todos** los glifos miden 1200/2000, el cursor de bloque `U+2588` incluido. Con otra
fuente hay que revisar ese número.

**La fuente se pide a Google Fonts en cada request.** `RendererModel::fetchFontCSS` llama a
`fonts.googleapis.com` y `fonts.gstatic.com` sin ninguna capa de caché, y la respuesta va con
`Cache-Control: no-cache`, así que cada visita invoca la función. Si Google no responde,
`fetchFontCSS` devuelve `""` y el SVG sale **sin fuente embebida** (4.4 KB en vez de 9 KB),
cayendo a `monospace` genérica — y como el path está calculado a 0.6 em, con otra métrica el
texto y el cursor se desalinean. Degrada, no rompe.

**El `;` separa líneas.** El servicio decodifica `%3B` a `;` y después parte por `;`, así que
un punto y coma dentro de una frase la corta en dos.

**GitHub cachea por URL.** Un cambio en esta plantilla no se ve en el perfil hasta que cambie
la URL: en `render/contenido.yml` del repo del perfil hay un parámetro `v=` que existe solo
para eso. Subilo al tocar este fork.
