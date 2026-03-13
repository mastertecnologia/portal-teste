function checkInscEstadual(theField,estado) {
    // var inscEst=retiraCaracteresInvalidos(theField.value);
    var regex = /[/.-\s]/g;
    var result = theField.replace(regex, '');
    var inscEst = result;
    
    if(inscEst.toLowerCase() != 'isento'){
        if (inscEst!="") {
            var dig8 = "/BA*/RJ*";
            var dig9 = "/AL*/AP*/AM*/CE*/ES*/GO*/MA*/MS*/PA*/PB*/PI*/RN*/RR*/SC*/SE*/TO*";
            var dig10 ="/PR*/RS*";
            var dig11 ="/MT*";
            var dig12 ="/SP*";
            var dig13 ="/AC*/MG*/DF*";
            var dig14 ="/PE*/RO*";
           
            if (dig8.indexOf("/"+estado+"*") != -1) {
                inscEst=inscEst.substr(0,8);
                tam=8;
            }
            else if (dig9.indexOf("/"+estado+"*") != -1) {
                inscEst=inscEst.substr(0,9);
                tam=9;
            }
            else if (dig10.indexOf("/"+estado+"*") != -1) {
                inscEst=inscEst.substr(0,10);
                tam=10;
            }
            else if (dig11.indexOf("/"+estado+"*") != -1) {
                inscEst=inscEst.substr(0,11);
                tam=11;
            }
            else if (dig12.indexOf("/"+estado+"*") != -1) {
                inscEst=inscEst.substr(0,12);
                tam=12;
            }
            else if (dig13.indexOf("/"+estado+"*") != -1) {
                inscEst=inscEst.substr(0,13);
                tam=13;
            }
            else if (dig14.indexOf("/"+estado+"*") != -1) {
                inscEst=inscEst.substr(0,14);
                tam=14;
            }
            // else {
            //     inscEst="";
            //     inscEstadual.disabled = true;
            //     tam=0;
            // }
        }
        if (inscEst!="") {
            if (estado=="AC") {
                inscEst=strzero(inscEst,13);
                primDigito=0;
                seguDigito=0;
                pesos="43298765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                pesos="543298765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                seguDigito=11-(soma%11);
                if (seguDigito>9)
                    seguDigito=0;
                   
                if ((parseInt(inscEst.substr(11,1))!=primDigito) || (parseInt(inscEst.substr(12,1))!=seguDigito)) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="AL") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                soma=soma*10;
                primDigito=soma%11;
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="AP") {
                inscEst=strzero(inscEst,9);
                if (inscEst.substr(0,2) != "03") {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                }
                else {
                    if (parseFloat(inscEst.substr(0,8))<3017000) {
                        p=5;
                        d=0;
                    }
                    else if (parseFloat(inscEst.substr(0,8))<3019022) {
                        p=9;
                        d=1;
                    }
                    else {
                        p=0;
                        d=0;   
                    }
                    primDigito=0;
                    pesos="98765432";
                    soma=p;
                    for(i=0;i<pesos.length;i++) {
                        soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                    }
                    primDigito=11-(soma%11);
                    if (primDigito==10)
                        primDigito=0;
                    else if (primDigito==11)
                        primDigito=d;
                    if (parseInt(inscEst.substr(8,1))!=primDigito) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                    else
                        theField.value=inscEst;
                }
            }
            else if (estado=="AM") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                if (soma<11)
                    primDigito=11-soma;
                else {
                    primDigito=soma%11;
                    if (primDigito<2)
                        primDigito=0;
                    else
                        primDigito=11-primDigito;
                }
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="BA") {
                inscEst=strzero(inscEst,8);
                primDigito=0;
                seguDigito=0;
                if ((parseInt(inscEst.substr(0,1))<6) || (parseInt(inscEst.substr(0,1))==8))
                    modulo=10;
                else
                    modulo=11;
                pesos="765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                seguDigito=modulo-(soma%modulo);
                if (seguDigito>9)
                    seguDigito=0;
                var inscEstAux=inscEst;
                inscEst=inscEst.substr(0,6) + "" + inscEst.substr(7,1) + "" + inscEst.substr(6,1);
                pesos="8765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=modulo-(soma%modulo);
                if (primDigito>9)
                    primDigito=0;
                inscEst=inscEst.substr(0,6) + "" + inscEst.substr(7,1) + "" + inscEst.substr(6,1);
                if ((parseInt(inscEst.substr(6,1))!=primDigito) || (parseInt(inscEst.substr(7,1))!=seguDigito)) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="CE") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="DF") {
                inscEst=strzero(inscEst,13);
                if (inscEst.substr(0,2) != "07") {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else {
                    primDigito=0;
                    seguDigito=0;
                    pesos="43298765432";
                    soma=0;
                    for(i=0;i<pesos.length;i++) {
                        soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                    }
                    primDigito=11-(soma%11);
                    if (primDigito>9)
                        primDigito=0;
                    pesos="543298765432";
                    soma=0;
                    for(i=0;i<pesos.length;i++) {
                        soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                    }
                    seguDigito=11-(soma%11);
                    if (seguDigito>9)
                        seguDigito=0;
                       
                    if ((parseInt(inscEst.substr(11,1))!=primDigito) || (parseInt(inscEst.substr(12,1))!=seguDigito)) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                    else
                        theField.value=inscEst;
                }
            }
            else if (estado=="ES") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="GO") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (inscEst.substr(0,8)=="11094402") {
                    if ((parseInt(inscEst.substr(8,1))!="0") && (parseInt(inscEst.substr(8,1))!="1")) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                }
                else {
                    if (primDigito==11)
                        primDigito=0;
                    else if (primDigito==10) {
                        if ((parseFloat(inscEst.substr(0,8))>=10103105) && (parseFloat(inscEst.substr(0,8))<=10119997))
                            primDigito=1;
                        else
                            primDigito=0;
                    }
                    if (parseInt(inscEst.substr(8,1))!=primDigito) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                    else
                        theField.value=inscEst;
                }
            }
            else if (estado=="MA") {
                inscEst=strzero(inscEst,9);
                if (inscEst.substr(0,2) != "12") {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else {
                    primDigito=0;
                    pesos="98765432";
                    soma=0;
                    for(i=0;i<pesos.length;i++) {
                        soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                    }
                    primDigito=11-(soma%11);
                    if (primDigito>9)
                        primDigito=0;
                    if (parseInt(inscEst.substr(8,1))!=primDigito) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                    else
                        theField.value=inscEst;
                }
            }
            else if (estado=="MT") {
                inscEst=strzero(inscEst,11);
                primDigito=0;
                pesos="3298765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(10,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="MS") {
                inscEst=strzero(inscEst,9);
                if (inscEst.substr(0,2) != "28") {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else {
                    primDigito=0;
                    pesos="98765432";
                    soma=0;
                    for(i=0;i<pesos.length;i++) {
                        soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                    }
                    primDigito=11-(soma%11);
                    if (primDigito>9)
                        primDigito=0;
                    if (parseInt(inscEst.substr(8,1))!=primDigito) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                    else
                        theField.value=inscEst;
                }
            }
            else if (estado=="MG") {
                inscEst=strzero(inscEst,13);
                inscEst=inscEst.substr(0,3)+"0"+inscEst.substr(3);
                primDigito=0;
                seguDigito=0;
                pesos="121212121212";
                soma=0;
                resultado=0;
                for(i=0;i<pesos.length;i++) {
                    resultado=parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1));
                    resultado=resultado+"";
                    for(j=0;j<resultado.length;j++) {
                        soma=soma+(parseInt(resultado.substr(j,1)));
                    }
                }
                somaAux=soma+"";
                primDigito=parseInt((parseInt(somaAux.substr(0,1))+1)+"0")-soma;
                if (primDigito>9)
                    primDigito=0;
                inscEst=inscEst.substr(0,3)+inscEst.substr(4);
                pesos="321098765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    mult=parseInt(pesos.substr(i,1));
                    if ((i>1) && (i<4))
                        mult=parseInt(pesos.substr(i,1))+10;
                    soma=soma+(parseInt(inscEst.substr(i,1))*mult);
                }
                seguDigito=11-(soma%11);
                if (seguDigito>9)
                    seguDigito=0;
                   
                if ((parseInt(inscEst.substr(11,1))!=primDigito) || (parseInt(inscEst.substr(12,1))!=seguDigito)) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
           
           
    
            else if (estado=="PA") {
                inscEst=strzero(inscEst,9);
                if (inscEst.substr(0,2) != "15") {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else {
                    primDigito=0;
                    pesos="98765432";
                    soma=0;
                    for(i=0;i<pesos.length;i++) {
                        soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                    }
                    primDigito=11-(soma%11);
                    if (primDigito>9)
                        primDigito=0;
                    if (parseInt(inscEst.substr(8,1))!=primDigito) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                    else
                        theField.value=inscEst;
                }
            }
            else if (estado=="PB") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="PR") {
                inscEst=strzero(inscEst,10);
                primDigito=0;
                seguDigito=0;
                pesos="32765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                pesos="432765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                seguDigito=11-(soma%11);
                if (seguDigito>9)
                    seguDigito=0;
                   
                if ((parseInt(inscEst.substr(8,1))!=primDigito) || (parseInt(inscEst.substr(9,1))!=seguDigito)) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="PE") {
                inscEst=strzero(inscEst,14);
                primDigito=0;
                pesos="5432198765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=primDigito-10;
                if (parseInt(inscEst.substr(13,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="PI") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="RJ") {
                inscEst=strzero(inscEst,8);
                primDigito=0;
                pesos="2765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(7,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="RN") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                soma=soma*10;
                primDigito=soma%11;
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="RS") {
                inscEst=strzero(inscEst,10);
                primDigito=0;
                pesos="298765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(9,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="RO") {
                inscEst=strzero(inscEst,14);
                primDigito=0;
                pesos="6543298765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito-=10;
                if (parseInt(inscEst.substr(13,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="RR") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="12345678";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                soma=soma*10;
                primDigito=soma%9;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="SC") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                soma=soma*10;
                primDigito=soma%11;
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="SP") {
                inscEst=strzero(inscEst,12);
                primDigito=0;
                seguDigito=0;
                pesos="13456780";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    mult=parseInt(pesos.substr(i,1));
                    if (i==7)
                        mult=10;
                    soma=soma+(parseInt(inscEst.substr(i,1))*mult);
                }
                primDigito=soma%11;
                if (primDigito>9)
                    primDigito=0;
                pesos="32098765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    mult=parseInt(pesos.substr(i,1));
                    if (i==2)
                        mult=10;
                    soma=soma+(parseInt(inscEst.substr(i,1))*mult);
                }
                seguDigito=soma%11;
                if (seguDigito>9)
                    seguDigito=0;
                   
                if ((parseInt(inscEst.substr(8,1))!=primDigito) || (parseInt(inscEst.substr(11,1))!=seguDigito)) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="SE") {
                inscEst=strzero(inscEst,9);
                primDigito=0;
                pesos="98765432";
                soma=0;
                for(i=0;i<pesos.length;i++) {
                    soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                }
                soma=soma*10;
                primDigito=11-(soma%11);
                if (primDigito>9)
                    primDigito=0;
                if (parseInt(inscEst.substr(8,1))!=primDigito) {
                    /* bootbox.alert("Inscrição Estadual inválida!");
                    $('.btn-success').prop('disabled','disabled');
                    $('#inscricaoestadual').val(''); */
                  
                   
                }
                else
                    theField.value=inscEst;
            }
            else if (estado=="TO") {
                inscEst=strzero(inscEst,9); // 11 Se tiver 2 algarismos
                //if ((inscEst.substr(2,2) != "01") && (inscEst.substr(2,2) != "02") && (inscEst.substr(2,2) != "03") && (inscEst.substr(2,2) != "99")) {
                //    bootbox.alert("Inscrição Estadual inválida!");
                //  
                //   
                //}
                //else {
                    primDigito=0;
                    //pesos="9800765432";
                    pesos="98765432";
                    soma=0;
                    for(i=0;i<pesos.length;i++) {
                        soma=soma+(parseInt(inscEst.substr(i,1))*parseInt(pesos.substr(i,1)));
                    }
                    primDigito=11-(soma%11);
                    if (primDigito>9)
                        primDigito=0;
                    if (parseInt(inscEst.substr(8,1))!=primDigito) {
                        /* bootbox.alert("Inscrição Estadual inválida!");
                        $('.btn-success').prop('disabled','disabled');
                        $('#inscricaoestadual').val(''); */
                      
                       
                    }
                    else
                        theField.value=inscEst;
                //}
            }
        }
    }

}

function strzero(dado,tam) {
    // var varTemp=retiraCaracteresInvalidos(dado);
    var varTemp = dado;
    for(i=varTemp.length;i<tam;i++)
    varTemp="0"+varTemp;
    return varTemp;
}

