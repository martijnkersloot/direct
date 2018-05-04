package nlp.information;

/**
 * Created by martijn on 06/02/2017.
 */
public class SyntaxInformation extends NLPInformation {

    private int id, dependentId, dependentBegin, dependentEnd, token;
    private String dependentText, relation;

    public SyntaxInformation(int sId, String sText, String sType, String sRelation, int sBegin, int sEnd, int sToken, int sDependentId, int sDependentBegin, int sDependentEnd, String sDependentText) {
        super(sText, sType, sBegin, sEnd);
        id = sId;
        relation = sRelation;
        token = sToken;
        dependentId = sDependentId;
        dependentBegin = sDependentBegin;
        dependentEnd = sDependentEnd;
        dependentText = sDependentText;
    }

    public int getId() {
        return id;
    }

    public int getDependentId() {
        return dependentId;
    }

    public int getToken() {
        return token;
    }

    public int getDependentBegin() {
        return dependentBegin;
    }

    public int getDependentEnd() {
        return dependentEnd;
    }

    public String getDependentText() {
        return dependentText;
    }

    public String getRelation() {
        return relation;
    }
}
