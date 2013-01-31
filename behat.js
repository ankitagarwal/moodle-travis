<script language="javascript">
function onMousebtClick(e)
{
    console.log(e);
    var targ;
    var click;
    var endstr;
    var startstr = "I go to ";
    var name;
    if (!e) var e = window.event;
    if (e.target)
        targ = e.target;
    else if (e.srcElement)
        targ = e.srcElement;
    switch (e.button)
    {
        case 0:
           click = "left click ";
        break;

        case 2:
            click = "right click ";
        break;
        case 1:
            click = "Middle click ";
        break;
    }
    name = targ.nodeName;
    console.log(click + targ.id + " AND ");
    var node =  document.getElementById(targ.id);
    console.log(node);
    if (name.toUpperCase() == "A") {
    	endstr = node.innerHTML;
    	if (!endstr)
        	endstr = node.href;
    } else if (node.innerHTML) {
        endstr = node.innerHTML;
        // Take out the trash.
        endstr = endstr.replace(new RegExp("/<.*>/", "mig"),"");
    } else {
        startstr = click + "on element with id ";
        endstr = node.id;
    }
    console.log (startstr + endstr);

}
document.onclick=onMousebtClick
</script>