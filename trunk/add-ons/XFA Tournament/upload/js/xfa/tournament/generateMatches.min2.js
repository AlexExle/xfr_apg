function XFATourn_render_fn(a,b,c){a.append(b.name)}function XFATourn_edit_fn(c,d,b){var a=$("<select />");for(i=0;i<bracketData.teams.length;i++){$("<option />",{value:bracketData.teams[i][0].id,text:bracketData.teams[i][0].name}).appendTo(a);$("<option />",{value:bracketData.teams[i][1].id,text:bracketData.teams[i][1].name}).appendTo(a)}a.val(d.id);c.html(a);a.focus();a.blur(function(){var h=a.find("option:selected").text();var g=a.val();var f=d.idx1-1;var e=d.idx2;var k=0;var j=0;b({name:h,id:g,idx1:-1,idx2:-1});for(i=0;i<bracketData.teams.length;i++){if(bracketData.teams[i][0].id==g){k=i;j=0}if(bracketData.teams[i][1].id==g){k=i;j=1}}bracketData.teams[k][j].name=bracketData.teams[f][e].name;bracketData.teams[k][j].id=bracketData.teams[f][e].id;bracketData.teams[f][e].name=h;bracketData.teams[f][e].id=g;XFATourn_load(bracketData)})}function XFATourn_load(b){var a=JSON.parse(JSON.stringify(b));$(".tournamentBracket").bracket({init:a,skipConsolationRound:($(".tournamentBracket").data("thirdPlace")===true?false:true),save:function(){},decorator:{edit:XFATourn_edit_fn,render:XFATourn_render_fn}});$(".tournamentBracket .tools").remove()}$(document).ready(function(){$("#generateMatchesForm").submit(function(){var a=$("<input type='hidden' name='bracketData'/>");a.val(JSON.stringify(bracketData));$(this).append(a);return true})});