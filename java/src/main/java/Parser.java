import nlp.information.SemanticInformation;
import nlp.information.SyntaxInformation;
import nlp.information.UMLSConcept;
import org.apache.ctakes.typesystem.type.refsem.OntologyConcept;
import org.apache.ctakes.typesystem.type.syntax.BaseToken;
import org.apache.ctakes.typesystem.type.syntax.ConllDependencyNode;
import org.apache.ctakes.typesystem.type.textsem.IdentifiedAnnotation;
import org.apache.uima.analysis_engine.AnalysisEngine;
import org.apache.uima.analysis_engine.AnalysisEngineDescription;
import org.apache.uima.fit.factory.AnalysisEngineFactory;
import org.apache.uima.fit.factory.JCasFactory;
import org.apache.uima.fit.pipeline.SimplePipeline;
import org.apache.uima.fit.util.JCasUtil;
import org.apache.uima.jcas.JCas;
import org.apache.uima.jcas.cas.FSArray;
import org.apache.uima.jcas.tcas.Annotation;
import org.w3c.dom.Attr;
import org.w3c.dom.Document;
import org.w3c.dom.Element;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;
import javax.xml.transform.OutputKeys;
import javax.xml.transform.Transformer;
import javax.xml.transform.TransformerFactory;
import javax.xml.transform.dom.DOMSource;
import javax.xml.transform.stream.StreamResult;
import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;
import java.io.StringWriter;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class Parser {
    private String fileName;
    private String fileContent;
    private AnalysisEngineDescription pipelineIncludingUmlsDictionaries;
    private AnalysisEngine ae;
    private JCas jcas;
    private Document xmlDoc;
    private double durationCreating;
    private boolean createdBefore;

    public static final String PROCESSOR = "/Users/martijn/IdeaProjects/ctakesrunner/cTAKES-3.2.2/desc/ctakes-clinical-pipeline/desc/analysis_engine/AggregatePlaintextFastUMLSProcessor.xml";
    //public static final String PROCESSOR = "/Users/martijn/IdeaProjects/ctakesrunner/cTAKES-3.2.2/desc/ctakes-ytex-uima/desc/analysis_engine/AggregatePlaintextUMLSProcessor.xml";
    public static final String CUI_REGEX = "cui: \"([^\"]*)\"";

    public Parser() {
        try {
            long startTime = System.nanoTime();

            pipelineIncludingUmlsDictionaries = AnalysisEngineFactory.createEngineDescriptionFromPath(PROCESSOR);
            ae = AnalysisEngineFactory.createEngine(pipelineIncludingUmlsDictionaries);
            jcas = JCasFactory.createJCas();

            long endTime = System.nanoTime();
            durationCreating = (endTime - startTime) / 1000000000.0;
            createdBefore = false;
            System.out.println("\n*** Done creating environment in " + durationCreating + " sec. ***\n");

        } catch (Exception e) {
            e.printStackTrace();
        }
    }

    public void setFile(String name, String content)
    {
        fileName = name;
        fileContent = content;
        setXML();
    }

    public void setText(String content)
    {
        fileName = "";
        fileContent = content;
        setXML();
    }

    private void setXML()
    {
        DocumentBuilderFactory docFactory = DocumentBuilderFactory.newInstance();
        DocumentBuilder docBuilder;
        try {
            docBuilder = docFactory.newDocumentBuilder();
            xmlDoc = docBuilder.newDocument();
        } catch (ParserConfigurationException e) {
            e.printStackTrace();
        }
    }

    public String getXML()
    {
        String output = null;

        long startTime;
        long endTime;
        double duration;

        try {
            startTime = System.nanoTime();
            // Set-up UIMA environment
            jcas.setDocumentText(fileContent);

            // Run cTAKES Pipeline
            SimplePipeline.runPipeline(jcas, ae);

            endTime = System.nanoTime();
            duration = (endTime - startTime) / 1000000000.0;
            System.out.println("\n*** Done parsing document in " + duration + " sec. ***\n");

            // Create document and document root

            Element xmlRoot = xmlDoc.createElement("AnnotatedOutput");
            xmlDoc.appendChild(xmlRoot);

            // Add filename to XML output
            Element xmlElementFileName = xmlDoc.createElement("FileName");
            xmlElementFileName.appendChild(xmlDoc.createTextNode(fileName));
            xmlRoot.appendChild(xmlElementFileName);

            // Add input string to XML output
            Element xmlElementInput = xmlDoc.createElement("Input");
            xmlElementInput.appendChild(xmlDoc.createTextNode(fileContent));
            xmlRoot.appendChild(xmlElementInput);

            // Add Syntax elements (WorkToken, NP, VP, ...) to XML output
            HashMap<String, ArrayList<SyntaxInformation>> syntaxMap = getSyntax();
            Element xmlSyntax = xmlDoc.createElement("Syntax");
            xmlSyntax = generateSyntaxXML(syntaxMap, xmlSyntax);
            xmlRoot.appendChild(xmlSyntax);

            // Add Semantic elements (ProcedureMention, SemanticArgument, ...) to XML output
            HashMap<String, ArrayList<SemanticInformation>> semanticMap = getSemantic();
            Element xmlSemantic = xmlDoc.createElement("Semantic");
            xmlSemantic = generateSemanticXML(semanticMap, xmlSemantic);
            xmlRoot.appendChild(xmlSemantic);

            Element xmlElementDuration = xmlDoc.createElement("Duration");

            Element xmlElementCreateDuration = xmlDoc.createElement("Environment");
            xmlElementCreateDuration.appendChild(xmlDoc.createTextNode(Double.toString(durationCreating)));

            Attr xmlSyntaxElementCreated = xmlDoc.createAttribute("existed");
            xmlSyntaxElementCreated.setValue(String.valueOf(createdBefore));
            xmlElementCreateDuration.setAttributeNode(xmlSyntaxElementCreated);

            xmlElementDuration.appendChild(xmlElementCreateDuration);

            Element xmlElementParseDuration = xmlDoc.createElement("Parsing");
            xmlElementParseDuration.appendChild(xmlDoc.createTextNode(Double.toString(duration)));
            xmlElementDuration.appendChild(xmlElementParseDuration);

            xmlRoot.appendChild(xmlElementDuration);

            // Generate output
            output = xmlToString(xmlDoc);
            setNull();

        } catch (Exception e) {
            e.printStackTrace();
        }

        startTime = System.nanoTime();
        endTime = System.nanoTime();
        duration = (endTime - startTime) / 1000000000.0;

        createdBefore = true;

        System.out.println("\n*** Done generating XML in " + duration + " sec. ***\n");

        return output;
    }

    private HashMap<String, ArrayList<SyntaxInformation>> getSyntax()
    {
        HashMap<String, ArrayList<SyntaxInformation>> syntaxMap = new HashMap<String, ArrayList<SyntaxInformation>>();

        for (Annotation chunk : JCasUtil.select(jcas, Annotation.class))
        {

            if(!syntaxMap.containsKey(chunk.getType().getShortName()))
            {
                syntaxMap.put(chunk.getType().getShortName(), new ArrayList<SyntaxInformation>());
            }

            if(chunk.getType().getName().contains("org.apache.ctakes.typesystem.type.syntax")) {
                int dependentBegin = 0;
                int dependentEnd = 0;
                String dependentText = null;
                String relation = null;
                int token = -1;
                int id = -1;
                int dependentId = -1;

                if(chunk.getType().getName().contains("org.apache.ctakes.typesystem.type.syntax.ConllDependencyNode")) {
                    ConllDependencyNode chunk2 = (ConllDependencyNode) chunk;
                    ConllDependencyNode head = chunk2.getHead();

                    if(head != null) {
                        dependentBegin = head.getBegin();
                        dependentEnd = head.getEnd();
                        dependentText = head.getCoveredText();
                        dependentId = head.hashCode();
                    }
                    relation = chunk2.getDeprel();

                    id = chunk2.hashCode();
                }

                if(chunk.getType().getName().contains("Token"))
                {
                    BaseToken baseToken = (BaseToken) chunk;
                    token = baseToken.getTokenNumber();
                }

                ArrayList<SyntaxInformation> list = syntaxMap.get(chunk.getType().getShortName());
                list.add(new SyntaxInformation(
                        id,
                        chunk.getCoveredText(),
                        chunk.getType().getShortName(),
                        relation,
                        chunk.getBegin(),
                        chunk.getEnd(),
                        token,
                        dependentId,
                        dependentBegin,
                        dependentEnd,
                        dependentText
                ));
                syntaxMap.put(chunk.getType().getShortName(), list);
            }
        }

        return syntaxMap;
    }

    private HashMap<String, ArrayList<SemanticInformation>> getSemantic()
    {
        HashMap<String, ArrayList<SemanticInformation>> semanticMap = new HashMap<String, ArrayList<SemanticInformation>>();

        for (IdentifiedAnnotation entity : JCasUtil.select(jcas, IdentifiedAnnotation.class))
        {
            if(!semanticMap.containsKey(entity.getType().getShortName()))
            {
                semanticMap.put(entity.getType().getShortName(), new ArrayList<SemanticInformation>());
            }

            ArrayList<SemanticInformation> list = semanticMap.get(entity.getType().getShortName());

            SemanticInformation information = new SemanticInformation(
                    entity.getCoveredText(),
                    entity.getType().getShortName(),
                    entity.getBegin(),
                    entity.getEnd(),
                    entity.getPolarity(),
                    entity.getSubject(),
                    entity.getHistoryOf()
            );

            FSArray concepts = entity.getOntologyConceptArr();

            //System.out.println(entity.getCoveredText() + " : " + concepts);
            if(concepts != null) {
                for (int i = 0; i < concepts.size(); i++) {
                    OntologyConcept ontologyConcept = (OntologyConcept) concepts.get(i);

                    String string = ontologyConcept.toString();
                    Pattern pattern = Pattern.compile(CUI_REGEX);
                    Matcher matcher = pattern.matcher(string);
                    String cui = null;

                    if (matcher.find()) {
                        cui = matcher.group(1);
                    }

                    information.addConcept(ontologyConcept.getCode(), ontologyConcept.getCodingScheme(), cui);
                }
            }

            list.add(information);
            semanticMap.put(entity.getType().getShortName(), list);
        }

        return semanticMap;
    }

    private Element generateSyntaxXML(HashMap<String, ArrayList<SyntaxInformation>> syntaxMap, Element xmlSyntax)
    {
        for (Map.Entry<String, ArrayList<SyntaxInformation>> entry : syntaxMap.entrySet())
        {
            Element xmlSyntaxElements = xmlDoc.createElement(entry.getKey() + "s");

            for(SyntaxInformation information : entry.getValue())
            {
                Element xmlSyntaxElement = xmlDoc.createElement(information.getType());

                Attr xmlSyntaxElementText = xmlDoc.createAttribute("text");
                xmlSyntaxElementText.setValue(information.getText());
                xmlSyntaxElement.setAttributeNode(xmlSyntaxElementText);

                Attr xmlSyntaxElementBegin = xmlDoc.createAttribute("begin");
                xmlSyntaxElementBegin.setValue(Integer.toString(information.getBegin()));
                xmlSyntaxElement.setAttributeNode(xmlSyntaxElementBegin);

                Attr xmlSyntaxElementEnd = xmlDoc.createAttribute("end");
                xmlSyntaxElementEnd.setValue(Integer.toString(information.getEnd()));
                xmlSyntaxElement.setAttributeNode(xmlSyntaxElementEnd);

                if(information.getId() >= 0) {
                    Attr xmlSyntaxElementId = xmlDoc.createAttribute("id");
                    xmlSyntaxElementId.setValue(Integer.toString(information.getId()));
                    xmlSyntaxElement.setAttributeNode(xmlSyntaxElementId);
                }

                if(information.getToken() >= 0) {
                    Attr xmlSyntaxElementToken = xmlDoc.createAttribute("token");
                    xmlSyntaxElementToken.setValue(Integer.toString(information.getToken()));
                    xmlSyntaxElement.setAttributeNode(xmlSyntaxElementToken);
                }

                if(information.getRelation() != null) {
                    Attr xmlSyntaxElementRelation = xmlDoc.createAttribute("relation");
                    xmlSyntaxElementRelation.setValue(information.getRelation());
                    xmlSyntaxElement.setAttributeNode(xmlSyntaxElementRelation);
                }

                if(information.getDependentText() != null)
                {
                    Attr xmlSyntaxElementDependentBegin = xmlDoc.createAttribute("dependentBegin");
                    xmlSyntaxElementDependentBegin.setValue(Integer.toString(information.getDependentBegin()));
                    xmlSyntaxElement.setAttributeNode(xmlSyntaxElementDependentBegin);

                    Attr xmlSyntaxElementDependentEnd = xmlDoc.createAttribute("dependentEnd");
                    xmlSyntaxElementDependentEnd.setValue(Integer.toString(information.getDependentEnd()));
                    xmlSyntaxElement.setAttributeNode(xmlSyntaxElementDependentEnd);

                    Attr xmlSyntaxElementDependentText = xmlDoc.createAttribute("dependentText");
                    xmlSyntaxElementDependentText.setValue(information.getDependentText());
                    xmlSyntaxElement.setAttributeNode(xmlSyntaxElementDependentText);

                    Attr xmlSyntaxElementDependentId = xmlDoc.createAttribute("dependentId");
                    xmlSyntaxElementDependentId.setValue(Integer.toString(information.getDependentId()));
                    xmlSyntaxElement.setAttributeNode(xmlSyntaxElementDependentId);
                }

                xmlSyntaxElements.appendChild(xmlSyntaxElement);
            }

            xmlSyntax.appendChild(xmlSyntaxElements);
        }

        return xmlSyntax;
    }

    private Element generateSemanticXML(HashMap<String, ArrayList<SemanticInformation>> semanticMap, Element xmlSemantic)
    {
        for (Map.Entry<String, ArrayList<SemanticInformation>> entry : semanticMap.entrySet())
        {
            Element xmlSemanticElements = xmlDoc.createElement(entry.getKey() + "s");

            for(SemanticInformation information : entry.getValue())
            {

                Element xmlSemanticElement = xmlDoc.createElement(information.getType());

                Attr xmlSemanticElementText = xmlDoc.createAttribute("text");
                xmlSemanticElementText.setValue(information.getText());
                xmlSemanticElement.setAttributeNode(xmlSemanticElementText);

                Attr xmlSemanticElementBegin = xmlDoc.createAttribute("begin");
                xmlSemanticElementBegin.setValue(Integer.toString(information.getBegin()));
                xmlSemanticElement.setAttributeNode(xmlSemanticElementBegin);

                Attr xmlSemanticElementEnd = xmlDoc.createAttribute("end");
                xmlSemanticElementEnd.setValue(Integer.toString(information.getEnd()));
                xmlSemanticElement.setAttributeNode(xmlSemanticElementEnd);

                Attr xmlSemanticElementPolarity = xmlDoc.createAttribute("polarity");
                xmlSemanticElementPolarity.setValue(Integer.toString(information.getPolarity()));
                xmlSemanticElement.setAttributeNode(xmlSemanticElementPolarity);

                Attr xmlSemanticElementSubject = xmlDoc.createAttribute("subject");
                xmlSemanticElementSubject.setValue(information.getSubject());
                xmlSemanticElement.setAttributeNode(xmlSemanticElementSubject);

                Attr xmlSemanticElementHistoryOf = xmlDoc.createAttribute("historyOf");
                xmlSemanticElementHistoryOf.setValue(Integer.toString(information.getHistoryOf()));
                xmlSemanticElement.setAttributeNode(xmlSemanticElementHistoryOf);

                ArrayList<String> concepts = new ArrayList<String>();

                for (UMLSConcept concept : information.getConcepts()) {
                    if(!concepts.contains(concept.getCode())) {
                        Element xmlOntologyConcept = xmlDoc.createElement("concept");

                        Attr xmlOntologyConceptSystem = xmlDoc.createAttribute("system");
                        xmlOntologyConceptSystem.setValue(concept.getSystem());
                        xmlOntologyConcept.setAttributeNode(xmlOntologyConceptSystem);

                        Attr xmlOntologyConceptCode = xmlDoc.createAttribute("code");
                        xmlOntologyConceptCode.setValue(concept.getCode());
                        xmlOntologyConcept.setAttributeNode(xmlOntologyConceptCode);

                        Attr xmlOntologyConceptCui = xmlDoc.createAttribute("cui");
                        xmlOntologyConceptCui.setValue(concept.getCui());
                        xmlOntologyConcept.setAttributeNode(xmlOntologyConceptCui);

                        xmlSemanticElement.appendChild(xmlOntologyConcept);
                        concepts.add(concept.getCode());
                    }
                }

                xmlSemanticElements.appendChild(xmlSemanticElement);
            }

            xmlSemantic.appendChild(xmlSemanticElements);
        }

        return xmlSemantic;
    }

    private void setNull()
    {
        jcas.reset();
        System.gc();
    }

    private String readFile(String path) throws IOException
    {
        BufferedReader reader = new BufferedReader( new FileReader(path));
        String line;
        StringBuilder stringBuilder = new StringBuilder();
        String ls = System.getProperty("line.separator");
        while((line = reader.readLine()) != null ) {
            stringBuilder.append(line);
            stringBuilder.append(ls);
        }
        stringBuilder.deleteCharAt(stringBuilder.length()-1);
        return stringBuilder.toString();
    }

    private String xmlToString(Document doc)
    {
        try {
            StringWriter sw = new StringWriter();
            TransformerFactory tf = TransformerFactory.newInstance();
            Transformer transformer = tf.newTransformer();
            transformer.setOutputProperty(OutputKeys.OMIT_XML_DECLARATION, "no");
            transformer.setOutputProperty(OutputKeys.METHOD, "xml");
            transformer.setOutputProperty(OutputKeys.INDENT, "yes");
            transformer.setOutputProperty("{http://xml.apache.org/xslt}indent-amount", "2");
            transformer.setOutputProperty(OutputKeys.ENCODING, "UTF-8");

            transformer.transform(new DOMSource(doc), new StreamResult(sw));
            return sw.toString();
        } catch (Exception ex) {
            throw new RuntimeException("Error converting to String", ex);
        }
    }
}