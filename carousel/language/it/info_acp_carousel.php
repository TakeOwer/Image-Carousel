<?php
/**
 * Forum Image Carousel Extension for phpBB.
 * @package salvocortesiano/carousel
 */

if (!defined('IN_PHPBB'))
{
    exit;
}

if (empty($lang) || !is_array($lang))
{
    $lang = [];
}

// Some characters you may want to copy&paste:
// ' » " " …

$lang = array_merge($lang, [
    'ACP_CAROUSEL_TITLE'            => 'Carosello Immagini Forum',
    'ACP_CAROUSEL_SETTINGS'         => 'Impostazioni Carosello',
    'ACP_CAROUSEL_SETTING_SAVED'    => 'Impostazioni del carosello di immagini del forum salvate con successo.',
    'ACP_CAROUSEL_DELETED'          => 'Carosello eliminato con successo.',
    
    'ACP_CAROUSEL_ADD'              => 'Aggiungi nuovo carosello',
    'ACP_CAROUSEL_NAME'             => 'Nome carosello',
	'ACP_CAROUSEL_ENABLED'          => 'Carosello Abilitato?',
    'ACP_CAROUSEL_NAME_EXPLAIN'     => 'Inserisci un nome univoco per questo carosello.',
    'ACP_CAROUSEL_TITLE_EXPLAIN'    => 'Inserisci il titolo che verrà visualizzato sopra il carosello.',
    'ACP_CAROUSEL_NONE'             => 'Nessun carosello è stato ancora creato.',
    
    'ACP_CAROUSEL_ENABLE'           => 'Abilita carosello',
    'ACP_CAROUSEL_ENABLE_EXPLAIN'   => 'Se impostato a sì, questo carosello verrà visualizzato nella pagina iniziale.',
    
    'ACP_CAROUSEL_FORUMS'           => 'Seleziona forum',
    'ACP_CAROUSEL_FORUMS_EXPLAIN'   => 'Seleziona i forum dai quali saranno estratte le immagini per il carosello. Tieni premuto CTRL per selezionare più forum.',
    
    'ACP_CAROUSEL_IMAGES'           => 'Immagini per forum',
    'ACP_CAROUSEL_IMAGES_EXPLAIN'   => 'Numero di immagini da estrarre da ciascun forum (1-10).',
    
    'ACP_CAROUSEL_SPEED'            => 'Velocità di scorrimento',
    'ACP_CAROUSEL_SPEED_EXPLAIN'    => 'Tempo in millisecondi tra le diapositive (1000 = 1 secondo).',
    
    'ACP_CAROUSEL_DIRECTION'        => 'Direzione di scorrimento',
    'ACP_CAROUSEL_DIRECTION_EXPLAIN'=> 'Scegli la direzione di scorrimento delle immagini nel carosello.',
    'ACP_CAROUSEL_DIRECTION_LTR'    => 'Da sinistra a destra',
    'ACP_CAROUSEL_DIRECTION_RTL'    => 'Da destra a sinistra',
    
    'LOG_CAROUSEL_SETTINGS'         => '<strong>Impostazioni del carosello di immagini del forum aggiornate</strong>',
    'LOG_CAROUSEL_ADDED'            => '<strong>Nuovo carosello aggiunto</strong><br>» %s',
    'LOG_CAROUSEL_UPDATED'          => '<strong>Carosello aggiornato</strong><br>» %s',
    'LOG_CAROUSEL_DELETED'          => '<strong>Carosello eliminato</strong><br>» %s',

    // Aggiungo le stringhe mancanti
    'ACTION'                        => 'Azione',
    'EDIT'                          => 'Modifica',
    'DELETE'                        => 'Elimina',
    'YES'                           => 'Sì',
    'NO'                            => 'No',
    'COLON'                         => ':',
    'SUBMIT'                        => 'Invia',
    'RESET'                         => 'Reimposta',
]);
