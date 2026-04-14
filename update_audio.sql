-- BaraQuiz : Neutralisation du support audio
-- Les questions restent mais perdent leur attribut audio (deviennent des questions texte)
UPDATE questions SET type_media = NULL, url_media = NULL WHERE type_media = 'audio';
