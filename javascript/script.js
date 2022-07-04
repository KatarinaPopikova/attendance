$(window).on("load",function (){
    reload();

});

function reload() {
     $("#loading").show();
    $("#body-table").empty();
    $(".remove-on-load").remove();

    addDataToDatabase().then(r =>
        addThead().then(r =>
        addNames()
        )
    );

}
async function addDataToDatabase() {
    await fetch('api.php?toDo=data')
        .then(response => response.json())
        .then(data => {
        });
}

async function addThead(){
    await fetch('api.php?toDo=getNameInfoClass')
        .then(response => response.json())
        .then(data => {
            makeTable(data.classesNames);
            showGraf(data.classesNames, data.countStudents);
        });
}

function makeTable(classesNames) {
    let thead = $("#head-table");
    classesNames.forEach(function (entry, index) {
        let th = document.createElement("th");
        $(th).addClass("remove-on-load")
        $(thead).append($(th).html((index+1) + "| "  + entry.substring(entry.length - 2, entry.length) + "." + entry.substring(5, 7) + ". "));
    });
    let th1 = document.createElement("th");
    $(thead).append($(th1).html("Účasti <img src=\'pictures/sort.svg\' alt=\"sortable\" height=\"15\" width=\"15\">"));
    let th2 = document.createElement("th");
    $(thead).append($(th2).html("Minúty <img src=\'pictures/sort.svg\' alt=\"sortable\" height=\"15\" width=\"15\">"));
    $(th1).addClass("cursor remove-on-load");
    $(th2).addClass("cursor remove-on-load");

    $(th1).on("click",function () {
        sortTable(classesNames.length+1);
    })
    $(th2).on("click",function () {
        sortTableNumber(classesNames.length+2);
    })
}

async function addNames(){
    await fetch('api.php?toDo=getAllNames')
        .then(response => response.json())
        .then(data => {
            addStudentNames(data.names, data.lectureTime);
            $("#loading").hide();

        });

}

function addStudentNames(names, times) {
    let tbody = $("#body-table");
    names.forEach(function (entry, index) {
        let tr = document.createElement("tr");
        let td = document.createElement("td");
        $(tr).append($(td).html(entry.meno.substr(entry.meno.indexOf(' ')+1) +
                                " " + entry.meno.substr(0,entry.meno.indexOf(' '))));
        addOtherColumns(times[index], tr);

        $(tbody).append(tr);
    });

}

function addOtherColumns(row,tr){
    for(let i = 1 ; i< row.length-2; i++){
        let td = document.createElement("td");
        $(tr).append($(td).html((row[i].minutes)));
        if(row[i].dontLeft)
            $(td).css({"color":"#BF8E6D"});

        $(td).addClass("minutes");
        $(td).on("click",function () {
            showClassUserDetail(row[0],row[i].id);
        })

    }
    let td1 = document.createElement("td");
    let td2 = document.createElement("td");

    $(tr).append($(td1).html(row[row.length-2]),$(td2).html((row[row.length-1])));
}

function showClassUserDetail(userId, classId){
    let id_s = {userId: userId,classId:classId};
    let request = new Request("api.php?toDo=forModal",{
        method: 'POST',
        body: JSON.stringify(id_s),
    });
    fetch(request)
        .then(response => response.json())
        .then(data => {
            showModal(data);

        });

}

function showModal(data){
    $("#student-name").html(data.student);
    makeModalTable(data.dataOnClass);
    $('#class-modal').modal({
        keyboard: false
    });
}

function makeModalTable(infoJoinLeft){
    let tbody = $("#modal-body-table");
    tbody.empty();
    infoJoinLeft.forEach(function (row) {
        let tr = document.createElement("tr");
        let td1 = document.createElement("td");
        let td2 = document.createElement("td");
        $(td1).html((row.akcia==="Joined")?"Príchod":"Odchod");
        $(td2).html(row.cas);


        tbody.append($(tr).append(td1,td2));

    });


}

let myChart;

function showGraf(classNames,countStudents){
    if(myChart)
        myChart.destroy();
    let ctx = document.getElementById('myChart');
    myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: classNames,
            datasets: [{
                label: 'Počet študentov',
                data: countStudents,
                backgroundColor: 'rgba(19, 29, 37, 0.41)',
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins:{
                legend:{
                    display:false,
                }
            }

        }
    });
}




function sortTableNumber(n){
    let table, rows, switching, i, x, y, shouldSwitch, dir, switchCount = 0;
    table = document.getElementById("sortableTable");
    switching = true;
    dir = "asc";

    while (switching) {
        switching = false;
        rows = table.rows;

        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];

            if (dir === "asc") {
                if (parseFloat(x.innerHTML) > parseFloat(y.innerHTML)) {
                    shouldSwitch = true;
                    break;
                }
            }

            else if (dir === "desc") {
                if (parseFloat(x.innerHTML) < parseFloat(y.innerHTML)) {
                    shouldSwitch = true;
                    break;
                }
            }
        }

        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchCount ++;
        }

        else {
            if (switchCount === 0 && dir === "asc") {
                dir = "desc";
                switching = true;
                switchCount ++;
            }
        }

        if(n!==0 && switchCount===1)
            sortTable(0);
    }
}



function sortTable(n){
    let table, rows, switching, i, x, y, shouldSwitch, dir, switchCount = 0;
    table = document.getElementById("sortableTable");
    switching = true;
    dir = "asc";

    while (switching) {
        switching = false;
        rows = table.rows;

        for (i = 1; i < (rows.length - 1); i++) {
            shouldSwitch = false;
            x = rows[i].getElementsByTagName("TD")[n];
            y = rows[i + 1].getElementsByTagName("TD")[n];

            if (dir === "asc") {
                if (x.innerHTML.localeCompare( y.innerHTML, "sk") === 1) {
                    shouldSwitch = true;
                    break;
                }
            }

            else if (dir === "desc") {
                if (x.innerHTML.localeCompare( y.innerHTML, "sk") === -1) {
                    shouldSwitch = true;
                    break;
                }
            }
        }

        if (shouldSwitch) {
            rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
            switching = true;
            switchCount ++;
        }

        else {
            if (switchCount === 0 && dir === "asc") {
                dir = "desc";
                switching = true;
                switchCount ++;
            }
        }

        if(n!==0 && switchCount===1)
            sortTable(0);
    }
}
