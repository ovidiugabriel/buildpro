
class Document : Node {
    public this() {}
    public Element createElement(string tag) {
        switch (tag) {
            case "html": return new HtmlElement();
            case "head": return new HeadElement();
            case "title": return new TitleElement();
            case "body": return new BodyElement();
            case "p": return new ParagraphElement();

            default: 
                writeln("unknown tag= " , tag);
                assert(0);
        }
    }
}
