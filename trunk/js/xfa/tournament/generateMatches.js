function XFATourn_render_fn(container, data, score)
{
    container.append(data.name)
}

function XFATourn_edit_fn(container, data, doneCb)
{
    var select = $("<select />");            
    for(i = 0; i < bracketData.teams.length; i++)
    {
        $("<option />", {value: bracketData.teams[i][0].id, text: bracketData.teams[i][0].name}).appendTo(select);
        $("<option />", {value: bracketData.teams[i][1].id, text: bracketData.teams[i][1].name}).appendTo(select);
    }
    select.val(data.id);
    
    container.html(select);
    
    select.focus();
    
    select.blur(function() {
        var selectedName    = select.find('option:selected').text();
        var selectedId      = select.val();              
        var newIdx1         = data.idx1 - 1;
        var newIdx2         = data.idx2;
        var previousIdx1    = 0;
        var previousIdx2    = 0;
    
        /* Let the script update */                                        
        doneCb({name: selectedName, id: selectedId, idx1 : -1, idx2: -1});
             
        /* Find out where it was before */               
        for (i = 0; i < bracketData.teams.length; i++)
        {
            if (bracketData.teams[i][0].id == selectedId)
            {
                previousIdx1 = i;
                previousIdx2 = 0;
            }
            
            if (bracketData.teams[i][1].id == selectedId)
            {
                previousIdx1 = i;
                previousIdx2 = 1;
            }
        }
             
        /* Let's move them */
        bracketData.teams[previousIdx1][previousIdx2].name  = bracketData.teams[newIdx1][newIdx2].name;
        bracketData.teams[previousIdx1][previousIdx2].id    = bracketData.teams[newIdx1][newIdx2].id;
        
        bracketData.teams[newIdx1][newIdx2].name  = selectedName;
        bracketData.teams[newIdx1][newIdx2].id    = selectedId;  
                          
        XFATourn_load(bracketData);             
    });
}

function XFATourn_load(loadBracketData)
{
    var initBracketData = JSON.parse(JSON.stringify(loadBracketData));
    
    $('.tournamentBracket').bracket({
        init: initBracketData,
        skipConsolationRound: ($('.tournamentBracket').data('thirdPlace') === true ? false : true),
        save: function(){}, /* without save() labels are disabled */
        decorator: {edit: XFATourn_edit_fn, render: XFATourn_render_fn}         
    });        
    
    $('.tournamentBracket .tools').remove();
}

$( document ).ready(function() {
    $('#generateMatchesForm').submit(function() {
        var $hidden = $("<input type='hidden' name='bracketData'/>");
        $hidden.val(JSON.stringify(bracketData));
        $(this).append($hidden);
        return true;
    });
});