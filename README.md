# wp-random-slug

Auto slug generator for Wordpress.

Generate slug on save, format looks like below.

```
https://tomoki-shishikura.com/news/250420250403595/

250420250403595 = makeRandStr(4) + date('Ymd') + $post_ID;
```
