/*
    This file is part of BibSonomy/PUMA CSL for WordPress.

    BibSonomy/PUMA CSL for WordPress is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    BibSonomy/PUMA CSL for WordPress is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with BibSonomy/PUMA CSL for WordPress.  If not, see <http://www.gnu.org/licenses/>.
 */

/* 
    Document   : bibsonomycsl.js
    Created on : 09/11/2014
    Author     : Sebastian Böttger <boettger@cs.uni-kassel.de>
    Description:
        Defines styles for BibSonomy publication lists
*/


function bindEvent(element, type, handler) {
    if (element.addEventListener) {
        element.addEventListener(type, handler, false);
    } else {
        element.attachEvent('on' + type, handler);
    }
}

function loadurl(dest, objnev) {

    
    try {
        xmlhttp = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject("Microsoft.XMLHTTP");
    } catch (e) {
        console.log(e);
    }
    xmlhttp.onreadystatechange = function() {
        triggered(objnev);
    };

    xmlhttp.open("GET", dest);
    xmlhttp.send(null);
}

function triggered(objnev) {

    if ((xmlhttp.readyState == 4) && (xmlhttp.status == 200)) {
        document.getElementById(objnev).innerHTML = xmlhttp.responseText;
    }
}


window.addEventListener("load", function() {
    var items = document.getElementsByClassName('bibsonomycsl_export');

    for (i = 0; i < items.length; ++i) {
        
        var item = items[i];
        
        item.addEventListener('click', function(event) {
            
            event = event || window.event;
            
            if (this.className.indexOf('bibtex')   > -1 || 
                this.className.indexOf('endnote')  > -1 ||
                this.className.indexOf('abstract') > -1)   {
                
                event.preventDefault();
            }

            // get href
            var href = this.childNodes[0].getAttribute("href");

            //get area (abs|bib)
            var area = this.childNodes[0].getAttribute("rel").substr(0, 3);

            //get id
            var id = this.childNodes[0].getAttribute("rel").substr(4);

            // toggle 
            var show = area + '-' + id; // element id to show

            var hide = new Array();

            //hide bibtex or abstract?
            if (area === 'bib') {
                hide[0] = 'abs' + '-' + id;
                hide[1] = 'end' + '-' + id;
            } else if (area === 'abs') {
                hide[0] = 'bib' + '-' + id;
                hide[1] = 'end' + '-' + id;
            } else if (area === 'end') {
                hide[0] = 'abs' + '-' + id;
                hide[1] = 'bib' + '-' + id;
            }
            
            //element to show
            var showVar = document.getElementById(show);
            
            if (showVar) {

                // hide the other one
                if (document.getElementById(hide[0])) {
                    document.getElementById(hide[0]).style.display = "none";
                }
                if (document.getElementById(hide[1])) {
                    document.getElementById(hide[1]).style.display = "none";
                }

                if (showVar.style.display === 'none') {
                    showVar.style.display = 'block';
                } else {
                    showVar.style.display = 'none';
                }
            } 
            // if this container for link to bibtex
            if (this.className.indexOf('bibtex') > -1 || this.className.indexOf('endnote') > -1) {

                loadurl(href, area + "-" + id);
            }

            return false;
        });
    }
});