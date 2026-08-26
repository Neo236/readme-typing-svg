# Modificaciones de este fork

Fork de [DenverCoder1/readme-typing-svg](https://github.com/DenverCoder1/readme-typing-svg) (MIT).
El `LICENSE` original se conserva intacto. Este archivo documenta **solo lo que cambiamos**.

Se usa para la cabecera del perfil [Neo236](https://github.com/Neo236), desplegado en Vercel.

| Qué | Dónde | Por qué |
|---|---|---|
| Cursor de bloque parpadeante al final de cada línea | `src/templates/main.php` | Durante el tipeo el borde de revelado ya hace de cursor, pero al completarse la frase no quedaba nada. Ahora el bloque late durante la pausa, como una terminal de verdad |
| El carácter del cursor se suma al subconjunto de la fuente | `src/models/RendererModel.php` | Google Fonts entrega solo los glifos pedidos; sin esto el cursor no se dibujaba |
| Punto de entrada en `api/` + resolución de autoload por `__DIR__` | `api/index.php`, `vercel.json` | El runtime PHP de Vercel exige que las funciones vivan en `api/`, y el `require` relativo al directorio de trabajo falla dentro de la lambda |

## Notas

- Al no pasar `lines`, en vez de redirigir a la demo del upstream redirige a este repositorio.
