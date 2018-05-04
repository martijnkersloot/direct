package nlp.information;

/**
 * Created by martijn on 06/02/2017.
 */
public class NLPInformation {
    private String text, type;
    private int begin, end;

    public NLPInformation(String sText, String sType, int sBegin, int sEnd)
    {
        text = sText;
        type = sType;
        begin = sBegin;
        end = sEnd;
    }

    public String getText()
    {
        return text;
    }

    public String getType()
    {
        return type;
    }

    public int getBegin()
    {
        return begin;
    }

    public int getEnd()
    {
        return end;
    }
}
