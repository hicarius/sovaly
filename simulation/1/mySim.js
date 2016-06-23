/**
 * @DEBUT : LES FONCTIONS POPUR AFFICHAGE DU CANVAS
 */

function init() {
    stage = new createjs.Stage(document.getElementById("raceCanvas"));
    w = stage.canvas.width;
    h = stage.canvas.height;
    middleCanvas = w/2;

    loader = new createjs.LoadQueue(false);
    loader.addEventListener("complete", loadComplete);

    manifest = [
        {src: "land/sky-01.jpg", id: "sky"},
        {src: "land/herbe.jpg", id: "ground"},
        {src: "land/rail-01.png", id: "rail"},
        {src: "land/rail-herbe-02.png", id: "rail2"},
        {src: "land/tree-01.png", id: "tree"},
    ];
    loader.loadManifest(manifest, true, "../images/");

}

function loadComplete()
{
    //cr�ation du sc�ne
    createScene();

    //cheval en course
    $.each(race.horses, function(i, item){
        //cr�ation du ch�val
        createHorse( item, firstLine + i );
    });

    text = new createjs.Text("Dist. : " + distance, "20px Arial", "#ff7700");
    text.y = 20;
    text.textBaseline = "alphabetic";
    stage.addChild(text);

    createjs.Ticker.setFPS(60);
    createjs.Ticker.addEventListener("tick", handleTick);
}

function createScene()
{
    var skyImg = loader.getResult("sky");
    sky = new createjs.Shape();
    sky.graphics.beginBitmapFill(skyImg).drawRect(0, 0, w + skyImg.width, skyImg.height);
    sky.tileW = skyImg.width;
    sky.y = 0;

    var groundImg = loader.getResult("ground");
    ground = new createjs.Shape();
    ground.graphics.beginBitmapFill(groundImg).drawRect(0, 0, w + groundImg.width, groundImg.height);
    ground.tileW = groundImg.width;
    ground.y = h - groundImg.height;

    var railImg = loader.getResult("rail");
    rail = new createjs.Shape();
    rail.graphics.beginBitmapFill(railImg).drawRect(0, 0, w + railImg.width, railImg.height);
    rail.tileW = railImg.width;
    rail.y = h - groundImg.height - railImg.height + 10;

    var rail2Img = loader.getResult("rail2");
    rail2 = new createjs.Shape();
    rail2.graphics.beginBitmapFill(rail2Img).drawRect(0, 0, w + rail2Img.width, rail2Img.height);
    rail2.tileW = rail2Img.width;
    rail2.y = h - rail2Img.height -  groundImg.height;

    var treeImg = loader.getResult("tree");
    tree = new createjs.Shape();
    tree.graphics.beginBitmapFill(treeImg).drawRect(0, 0, w + treeImg.width, treeImg.height);
    tree.tileW = treeImg.width;
    tree.y = h - groundImg.height - rail2Img.height - treeImg.height + 10;

    stage.addChild(sky, ground, tree, rail2, rail);
    firstLine  = rail.y - 40;
}

function createHorse(data, begin)
{
    //Horse sprite
    var horse = new createjs.SpriteSheet({
        framerate: data.framerate * multiplicateurVitesse,
        images: ["../images/" + race.type + "/50Horse" + data.color + ".png"],
        frames: {"width": 160, "height": 120, "count": 12},
        animations: {}
    });
    var horseSprite = new createjs.Sprite(horse, "run");
    horseSprite.y = begin;
    horses.push(horseSprite);

    //Jockey casaque sprite
    var casaque =  new createjs.SpriteSheet(
        {
            images: ["../images/galop/50JockeySilk.png"],
            frames: {"width":160, "height":120, "count": 12},
            animations: {}
        }
    );
    var casaqueSprite = new createjs.Sprite(casaque, "run");
    casaqueSprite.y = horseSprite.y; //synchronisation avec horse.y
    casaqueSprite.spriteSheet.framerate = horseSprite.spriteSheet.framerate; //synchronisation avec horse.framerate
    casaques.push(casaqueSprite);

    //Jockey pantalon, ombre sprite
    var pant =  new createjs.SpriteSheet(
        {
            images: ["../images/galop/50Static.png"],
            frames: {"width":160, "height":120, "count": 12},
            animations: {}
        }
    );
    var pantSprite = new createjs.Sprite(pant, "run");
    pantSprite.y = horseSprite.y; //synchronisation avec horse.y
    pantSprite.spriteSheet.framerate = horseSprite.spriteSheet.framerate; //synchronisation avec horse.framerate
    pants.push(pantSprite);

    //Ajout des sprites (horse, casaque, jockeys) dans la sc�ne
    stage.addChild(horseSprite,casaqueSprite, pantSprite);

    //num�ro du ch�val dans la course
    var number =  new createjs.SpriteSheet(
        {
            images: ["../images/galop/50Unit_" +  (horses.length) + ".png"],
            frames: {"width":160, "height":120, "count": 12},
            animations: {}
        }
    );
    var numberSprite = new createjs.Sprite(number, "run");
    numberSprite.y = horseSprite.y; //synchronisation avec horse.y
    numberSprite.spriteSheet.framerate = horseSprite.spriteSheet.framerate; //synchronisation avec horse.framerate
    numbers.push(numberSprite);

    stage.addChild(numberSprite);
}

function handleTick(event)
{
    if(middleReach == false) {
        $.each(horses, function (i, item) {
            if (item.x <= middleCanvas && middleReach == false) {
                item.x += race.horses[i].vitesse[0] * 3;
                currentVitesse[i] = race.horses[i].vitesse[0];
            } else {
                if(middleReach == false) {
                    middleReach = true;
                    horseFirst = item;
                }
            }
        });
    }

    if( middleReach  ){
        generateHorseVitesse();
        animationScene(event);
    }

    synchronizeHorseObject();

    //Debugger
    text.text = "Dist. : " + distance + "/" + race.lenght + "m \n";
    text.text += "cv-h1 : " + currentVitesse[0] + " / lv-h1 : " + lastVitesse[0] + " / x : " + horses[0].x + " / y: " + horses[0].y + " \n";
    text.text += "cv-h2 : " + currentVitesse[1] + " / lv-h2 : " + lastVitesse[1] + " / x : " + horses[1].x + " / y: " + horses[1].y + " \n";
    text.text += "cv-h3 : " + currentVitesse[2] + " / lv-h3 : " + lastVitesse[2] + " / x : " + horses[2].x + " / y: " + horses[2].y + " \n";

    //distance
    if(count%8 == 0) {
        distance++;
        updateDistance = true
    }else{
        updateDistance = false;
    }

    count++;

    stage.update(event);

}

function synchronizeHorseObject(){
    $.each(horses, function(i, item){
        casaques[i].x = item.x;
        pants[i].x = item.x;
        numbers[i].x = item.x;
    });
}

function animationScene(event)
{
    var deltaS = event.delta / 1000 * multiplicateurVitesse;
    ground.x = (ground.x - deltaS * 150) % ground.tileW;
    rail.x = (rail.x - deltaS * 150) % rail.tileW;
    rail2.x = (rail2.x - deltaS * 110) % rail2.tileW;
    tree.x = (tree.x - deltaS * 80) % tree.tileW;
    sky.x = (sky.x - deltaS * 3) % sky.tileW;
}

/**
 * @FIN : LES FONCTIONS POPUR AFFICHAGE DU CANVAS
 */



/**
 * @DEBUT : LES FONCTIONS NECESSAIRES POUR SIMULER LA COURSE
 */
function generateHorseVitesse()
{
    ecartP[0] = false;
    $.each(horses, function(i, item){
        var ecart;
        if(distance%100 == 0){
            if( updateDistance == true) {
                lastVitesse[i] = currentVitesse[i];
                currentVitesse[i] = race.horses[i].vitesse[distance];
            }
        }

        // si nouvelle vitesse inf�rieur � l'anciene
        if(lastVitesse[i] < currentVitesse[i]){
            ecart = (currentVitesse[i] - lastVitesse[i])/5;

            // si cheval n'est pas le premier
            if(item.id != horseFirst.id){
                // reduction du course avec l'ecart
                item.x -= ecart;
            }else{
                ecartP[0] = true;
                ecartP[1] = ecart;
                ecartP[2] = '+';
                ecartP[3] = currentVitesse[i];
            }


            // sinon
        }else if(lastVitesse[i] > currentVitesse[i]){
            ecart = (lastVitesse[i] - currentVitesse[i])/5;

            // si cheval n'est pas le premier
            if(item.id != horseFirst.id){
                // augmentation du course avec l'ecart
                item.x += ecart;
            }else{
                ecart = (lastVitesse[i] - currentVitesse[i])/5;
                //si premier
                ecartP[0] = true;
                ecartP[1] = ecart;
                ecartP[2] = '-';
                ecartP[3] = currentVitesse[i];
            }
        }

        if(ecartP[0] == true){
            $.each(horses, function(i, item){
                if(item.id != horseFirst.id){
                    if(ecartP[3] > currentVitesse[i]) { //si first vitesse > current vitesse
                        item.x -= (ecartP[3] - currentVitesse[i])/5;
                    }else if(ecartP[3] < currentVitesse[i]) { //si first vitesse > current vitesse
                        item.x += (currentVitesse[i] - ecartP[3])/3;
                    }

                }
            });
        }


        if(item.x >= middleCanvas){
            horseFirst = item;
        }
    });


}

/**
 * @FIN : LES FONCTIONS NECESSAIRES POUR SIMULER LA COURSE
 */