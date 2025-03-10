function resize(){
    const container = document.getElementById("container");
    if (container != null){
        const items = container.children;

        container.style.width = window.innerWidth * 0.8 + "px";
        container.style.height = window.innerWidth * 0.3 + "px";

        for (j = 0; j < items.length; j++){
            items[j].style.width = parseFloat(container.style.width.slice(container.style.width, -2)) / (items.length + 1) + "px";
            items[j].style.height = items[j].style.width;
            items[j].style.fontSize = parseFloat(container.style.width.slice(container.style.width, -2)) / (8 * items.length) + "px";
        }
    }
}

resize();
onresize = resize;