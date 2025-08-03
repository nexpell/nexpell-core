/**
 * @license Copyright (c) 2003-2020, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function(config) {
    config.codeSnippet_theme = 'school_book';
    config.enterMode = CKEDITOR.ENTER_BR; // <br /> bei Enter
    config.shiftEnterMode = CKEDITOR.ENTER_P; // <p> bei Shift + Enter
    config.autoParagraph = false;
    config.entities = false;
    config.basicEntities = false;
    config.encodeEntities = false;
    config.forceSimpleAmpersand = true;
    config.fillEmptyBlocks = false;
    //config.removePlugins = 'elementspath';
    //config.resize_enabled = false;
    config.FormatOutput = false;

    // Wichtig: Erlaube alle Inhalte inkl. Klassen, Styles etc.
    config.allowedContent = true;
};


//CKEDITOR.replace('message');