# La Fabrique à Cookies

Un Cookie Clicker simple en PHP natif, sans framework ni base de données. La progression est conservée dans la session PHP.

## Lancer le projet

Avec PHP 8.0 ou plus récent :

```bash
php -S localhost:8000
```

Puis ouvrir <http://localhost:8000>.

## Fonctionnalités

- clic principal avec animation et puissance améliorable ;
- quatre producteurs automatiques avec coûts progressifs ;
- production hors onglet calculée côté serveur, jusqu’à 8 heures ;
- sauvegarde de la partie en session PHP ;
- interface responsive, utilisable au clavier ;
- requêtes protégées par jeton CSRF.
