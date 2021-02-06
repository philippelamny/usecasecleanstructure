# usecasecleanstructure


## Utiliser Docker pour utiiser un container avec php/composer
> docker-compose build
> docker-compose up -d

## télécharger les vendors
> composer install


## Utiliser la console
> php .\lib\console.php

## Exemple commande permettant de générer des class pour un UseCase respectant la clean archi
> php .\lib\console.php usecase:create:structure --help


> php .\lib\console.php usecase:create:structure --core-path=core --prefix-namespace=core

- core-path est le dossier principal qui contiendra le dossier Domains
- prefix-namespace est le préfix des namespaces des class commençant par Domains

Ici on aura comme:
    - **dossier** core/Domains/....
    - **namespace** Core\Domains\

