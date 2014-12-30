//Wikiwyg4Pukiwiki.js start
proto = new Subclass('Wikiwyg.ClientServer', 'Wikiwyg');
proto.saveChanges = function() {
    var self = this;
    this.current_mode.toHtml( function(html) { self.fromHtml(html) });
    this.displayMode();
}
proto.modeClasses = [
    'Wikiwyg.Wysiwyg',
    'Wikiwyg.Wikitext.ClientServer',
    'Wikiwyg.Preview'
];
proto = new Subclass('Wikiwyg.Wikitext.ClientServer', 'Wikiwyg.Wikitext');
proto.convertWikitextToHtml = function(wikitext, func) {
    var pageid=Wikiwyg.pageid();
    var pagerev='';
    var pagedate=Wikiwyg.pagedate();
    var pageprefix='';
    var pagesuffix='';
    var pagedo=Wikiwyg.pagedo();
    var dwpd = 'action='+pageid+'&rev='+pagerev+'&date='+pagedate+'&prefix='+pageprefix+'&suffix='+pagesuffix+'&do='+pagedo+'&wikitext=';
//    var postdata = 'action=wikiwyg_wikitext_to_html;content=' + 
    var postdata = dwpd + encodeURIComponent(wikitext);
    alert('Wikiwyg.uri()='+Wikiwyg.uri()+'\npostdata'+postdata);
    Wikiwyg.liveUpdate(
        'POST',
        Wikiwyg.uri(),
        postdata,
        func
    );
}
proto.config = Wikiwyg.Wikitext.prototype.config;
proto.config.markupRules.link = ['bound_phrase', '[[', ']]'];
proto.config.markupRules.bold = ['bound_phrase', '\'\'', '\'\''];
proto.config.markupRules.code = ['bound_phrase', ' ', ''];
proto.config.markupRules.italic = ['bound_phrase', '\'\'\'', '\'\'\''];
proto.config.markupRules.underline = ['bound_phrase', '%%%', '%%%'];
proto.config.markupRules.strike = ['bound_phrase', '%%', '%%'];
proto.config.markupRules.p = ['start_lines', ''];
proto.config.markupRules.pre = ['start_lines', ' '];
proto.config.markupRules.h1 = ['start_lines', '*'];
proto.config.markupRules.h2 = ['start_lines', '**'];
proto.config.markupRules.h3 = ['start_lines', '***'];
proto.config.markupRules.h4 = ['start_lines', ''];
proto.config.markupRules.ordered = ['start_lines', '+ '];
proto.config.markupRules.unordered = ['start_lines', '- '];
proto.config.markupRules.indent = ['start_lines', '>'];
proto.config.markupRules.hr = ['line_alone', '----'];
proto.config.markupRules.table = ['line_alone', '| A | B | C |\n|   |   |   |\n|   |   |   |'];
//Wikiwyg4Pukiwiki.js end

// Wikiwygify elements (may depend on your skin)
var wikiwyg_divs = [];
onload = function() {
    var config = {
        doubleClickToEdit: true,
        toolbar: {
            imagesLocation: SKIN_DIR + 'wikiwyg/images/'
        }
    };
    var elements = document.getElementsByTagName('div');
    var divs = [];
    for (var i = 0; i < elements.length; i++) {
        var id = "";
        if (elements[i].attributes['id']){
            var id = elements[i].attributes['id'].value;
        }
        // The page contents starts with the div class page. Sections should be added here also..
        if (id=="body") {
            divs.push(elements[i]);
        }
    }
    for (var i in divs) {
        var wikiwyg = new Wikiwyg.ClientServer();
        wikiwyg.createWikiwygArea(divs[i], config);
        wikiwyg_divs.push(wikiwyg);
    }
}
