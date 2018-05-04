package nlp.information;

import java.util.ArrayList;

/**
 * Created by martijn on 06/02/2017.
 */
public class SemanticInformation extends NLPInformation {
    private ArrayList<UMLSConcept> concepts;
    private int polarity, historyOf;
    private String subject;

    public SemanticInformation(String sText, String sType, int sBegin, int sEnd, int sPolarity, String sSubject, int sHistoryOf) {
        super(sText, sType, sBegin, sEnd);
        concepts = new ArrayList<UMLSConcept>();
        polarity = sPolarity;
        subject = sSubject;
        historyOf = sHistoryOf;
    }

    public void addConcept(String code, String system, String cui)
    {
        concepts.add(new UMLSConcept(code, system, cui));
    }

    public ArrayList<UMLSConcept> getConcepts()
    {
        return concepts;
    }

    public int getPolarity()
    {
        return polarity;
    }

    public boolean hasConcepts()
    {
        return !concepts.isEmpty();
    }

    public String getSubject()
    {
        return subject;
    }
    public int getHistoryOf()
    {
        return historyOf;
    }
}
