package nlp.information;

/**
 * Created by martijn on 06/02/2017.
 */
public class UMLSConcept {
    private String code, system, cui;

    public UMLSConcept(String uCode, String uSystem, String uCui)
    {
        code = uCode;
        system = uSystem;
        cui = uCui;
    }

    public String getCode()
    {
        return code;
    }

    public String getSystem()
    {
        return system;
    }

    public String getCui()
    {
        return cui;
    }
}
