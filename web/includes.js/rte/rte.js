var rte_name = "inhalt_content";
var rte_form_name = "inhalt";
var rte_image_path = "images/editor/";

var rte_fonts = ["Arial", "Arial Black", "Arial Narrow", "Courier New", "Century Gothic", "Comic Sans MS", "Impact", "Tahoma", "Times New Roman", "Trebuchet MS", "Verdana"];
var rte_formats = ["Paragraph", "Header 1", "Header 2", "Header 3", "Header 4", "Header 5", "Header 6"];

////////////////////////////////////////////////////////////////////////////////
// ...
////////////////////////////////////////////////////////////////////////////////

function rte_mouse_ca_down()
	{
	for(i in rte_formats)
		{
		if(this.innerHTML == rte_formats[i])
			{
			document.getElementById(rte_name).contentWindow.document.execCommand("formatblock", false, "<" + ["p", "h1", "h2", "h3", "h4", "h5", "h6"][i] + ">");
			document.getElementById("format1").innerHTML = rte_formats[i];

			break;
			}
		}

	for(i in rte_fonts)
		{
		if(this.innerHTML == rte_fonts[i])
			{
			document.getElementById(rte_name).contentWindow.document.execCommand("fontname", false, rte_fonts[i].toLowerCase());
			document.getElementById("fontface1").innerHTML = rte_fonts[i];

			break;
			}
		}

	for(i = 1; i < 8; i = i + 1)
		{
		if(this.innerHTML == i)
			{
			document.getElementById(rte_name).contentWindow.document.execCommand("fontsize", false, i);
			document.getElementById("fontsize1").innerHTML = i;

			break;
			}
		}

	this.style.color = "#000000";
	this.style.backgroundColor = "#FFFFFF";

	rte_hide();
	}

function rte_mouse_ca_out()
	{
	this.style.color = "#000000";
	this.style.backgroundColor = "#FFFFFF";
	}

function rte_mouse_ca_over()
	{
	this.style.color = "#FFFFFF";
	this.style.backgroundColor = "#4080C0";

	document.getElementById(rte_name).contentWindow.focus();
	}

////////////////////////////////////////////////////////////////////////////////
// ...
////////////////////////////////////////////////////////////////////////////////

function rte_mouse_da_out()
	{
	this.className = "rte_dropdown_color_out";
	}

function rte_mouse_da_over()
	{
	this.className = "rte_dropdown_color_over";
	}

