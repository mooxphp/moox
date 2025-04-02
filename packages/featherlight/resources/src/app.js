import Alpine from "alpinejs";
import ajax from "@imacrayon/alpine-ajax";
import "./app.css";

window.Alpine = Alpine;

Alpine.plugin(ajax);

Alpine.start();
