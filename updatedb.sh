#!/bin/dash

# ----- Fonctions -----

# Fonction de mise à jour des données
update_data() {
  echo -n 'Nom de la  base de données : '
  read DATABASE
  echo -n "Nom d'utilisateur : "
  read USER
  echo -n "Mot de passe : "
  read PASSWORD

  echo "$DATABASE:$USER:$PASSWORD" > $FILENAME
}

# Fonction pour obtenir les données
get_data() {
  if [ ! -f $FILENAME ]; then
    echo '-- Première utilisation du script --'
    update_data
  else
    echo "-- Mise à jour des informations sur la bd --"
    DATABASE=$(cut -d : -f 1 $FILENAME)
    USER=$(cut -d : -f 2 $FILENAME)
    PASSWORD=$(cut -d : -f 3 $FILENAME)
  fi
}

update() {
  cp $GAZETTE_FILE $GAZETTE_FILE.tmp
  sed -E -e "s/define\('BD_NAME','[a-zA-Z_-]*'\)/define\('BD_NAME','$DATABASE'\)/" -e "s/define\('BD_USER','[a-zA-Z_-]*'\)/define\('BD_USER','$USER'\)/" -e "s/define\('BD_PASS','[a-zA-Z_-]*'\)/define\('BD_PASS','$PASSWORD'\)/" $GAZETTE_FILE.tmp > $GAZETTE_FILE
rm -f $GAZETTE_FILE.tmp
}

# ----- Main -----

# Usage
USAGE='updatedb.sh [--reset|--help]'

# Vérification des paramètres
if [ $# -gt 1 ]; then
  echo "Erreur : il y a trop d'arguments"
  echo $USAGE
  exit 1
fi

# Variables
FILENAME='.updatedb.txt'
GAZETTE_FILE='php/bibli_gazette.php'
DATABASE=''
USER=''
PASSWORD=''

# Traitement des paramètres
if [ $# -eq 1 ]; then
  if [ "$1" = "--reset" ]; then
    echo "-- Modification des données --\n"
    update_data
    exit 0
  fi
  if [ "$1" = "--help" ]; then
    echo "-- Mise à jour des informations sur la bd --\n"
    echo "Mettre à jour facilement les informations sur la base de données\naprès un merge.\n"
    echo $USAGE
    exit 0
  else
    echo "Erreur : mauvais argument '$1'"
    echo $USAGE
  fi
fi

# Mette à jour les infos sur la bd
get_data
update
echo "\nDonnées mises à jour !"

exit 0