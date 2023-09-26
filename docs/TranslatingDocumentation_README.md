## To Do Translation of README files

1. Update the asciidoctor attributes files in the docs/asciidoctor/locale directory 
   * If you are adding a new language, add a file with the new language code. For example, if you are translating to French, you would create a file named attributes-fr.adoc.
2. Update the ini files in the docs/ini directory
   * If you are adding a new language, add a file with the new language code. For example, if you are translating to French, you would create a file named fr.ini.


## To Generate Translated Documentation

1. Install [asciidoctor](https://docs.asciidoctor.org/asciidoctor/latest/install/)
2. Edit the translate_docs.sh script to include any new language codes
3. Run the translate_docs.sh script
