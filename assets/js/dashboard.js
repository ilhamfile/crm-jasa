/*
==========================================================
 PT. LENTERA STATISTICS INDONESIA
 Dashboard JS
 Version : 3.0
==========================================================
*/

const Dashboard = {

    init() {

        this.cardHover();
        this.initTooltips();
        this.highlightMenu();
        this.tableResponsive();
        this.autoHideAlert();
        this.scrollTop();

        // Dashboard
        this.initDataTable();
        this.initTableTabs();

    },

    /*
    ==========================================
    Card Animation
    ==========================================
    */
    cardHover() {

        document.querySelectorAll(".summary-card,.app-card").forEach(card => {

            card.addEventListener("mouseenter", function () {
                this.style.transform = "translateY(-5px)";
            });

            card.addEventListener("mouseleave", function () {
                this.style.transform = "";
            });

        });

    },

    /*
    ==========================================
    Bootstrap Tooltip
    ==========================================
    */
    initTooltips() {

        if (typeof bootstrap === "undefined") return;

        document
            .querySelectorAll('[data-bs-toggle="tooltip"]')
            .forEach(el => new bootstrap.Tooltip(el));

    },

    /*
    ==========================================
    Active Menu
    ==========================================
    */
    highlightMenu() {

        const url = window.location.pathname;

        document.querySelectorAll(".menu-bar a").forEach(menu => {

            const href = menu.getAttribute("href");

            if (!href) return;

            if (url.includes(href)) {

                menu.classList.add("active");

            }

        });

    },

    /*
    ==========================================
    Responsive Table
    ==========================================
    */
    tableResponsive() {

        const table = document.querySelector(".table-responsive");

        if (!table) return;

        if (window.innerWidth < 768) {

            table.classList.add("shadow-soft");

        } else {

            table.classList.remove("shadow-soft");

        }

    },

    /*
    ==========================================
    Auto Hide Alert
    ==========================================
    */
    autoHideAlert() {

        const alert = document.querySelector(".alert");

        if (!alert) return;

        setTimeout(() => {

            alert.style.transition = ".5s";
            alert.style.opacity = "0";

            setTimeout(() => {

                alert.remove();

            }, 500);

        }, 4000);

    },

    /*
    ==========================================
    Scroll To Top
    ==========================================
    */
    scrollTop() {

        const btn = document.getElementById("scrollTop");

        if (!btn) return;

        window.addEventListener("scroll", () => {

            if (window.scrollY > 250) {

                btn.classList.add("show");

            } else {

                btn.classList.remove("show");

            }

        });

        btn.addEventListener("click", () => {

            window.scrollTo({

                top: 0,
                behavior: "smooth"

            });

        });

    },

    /*
    ==========================================
    DataTable
    ==========================================
    */
    initDataTable() {

        if (typeof $ === "undefined") return;
        if (typeof $.fn.DataTable === "undefined") return;
        if (!document.getElementById("dataTable")) return;
        this.dataTable = $('#dataTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthChange: false,
            ordering: true,
            info: true,
            autoWidth: false,
            order: [[5, "asc"]],
            columnDefs: [
                {
                    targets: 0,
                    responsivePriority: 1
                },
                {
                    targets: 6,
                    responsivePriority: 2,
                    orderable: false,
                    searchable: false
                }
            ],
            dom: "rtip"
        });

        const searchInput = document.getElementById("tableSearch");
        const dataTable = this.dataTable;
        if (searchInput) {
            searchInput.addEventListener("input", function () {
                dataTable.search(this.value).draw();
            });
        }
    },

    /*
    ==========================================
    Filter Tab
    ==========================================
    */
    initTableTabs() {
    
        const tabs = document.querySelectorAll(".table-tab");
    
        if (!tabs.length) return;
        if (!this.dataTable) return;
        if (typeof $ === "undefined") return;
    
        let activeFilter = "all";
    
        $.fn.dataTable.ext.search.push(function (
            settings,
            data,
            dataIndex
        ) {
    
            if (!settings.nTable || settings.nTable.id !== "dataTable") {
                return true;
            }
    
            const row = settings.aoData[dataIndex].nTr;
    
            if (!row) return true;
    
            const status = (
                row.dataset.status || ""
            ).toLowerCase();
    
            const deadline = Number(
                row.dataset.deadline || 0
            );
    
            const todayDate = new Date();
    
            todayDate.setHours(0, 0, 0, 0);
    
            const today = Math.floor(
                todayDate.getTime() / 1000
            );
    
            const sevenDays = today + (86400 * 7);
    
            switch (activeFilter) {
    
                case "belum":
                    return status === "belum";
    
                case "tertunda":
                    return status === "tertunda";
    
                case "selesai":
                    return status === "selesai";
    
                case "deadline":
                    return (
                        deadline >= today &&
                        deadline <= sevenDays
                    );
    
                default:
                    return true;
    
            }
    
        });
    
        tabs.forEach(tab => {
    
            tab.addEventListener("click", () => {
    
                tabs.forEach(item => {
                    item.classList.remove("active");
                });
    
                tab.classList.add("active");
    
                activeFilter = tab.dataset.filter || "all";
    
                this.dataTable.draw();
    
            });
    
        });
    
    }

};

/*
==========================================================
START
==========================================================
*/

document.addEventListener("DOMContentLoaded", () => {

    Dashboard.init();

});

/*
==========================================================
WINDOW RESIZE
==========================================================
*/

window.addEventListener("resize", () => {

    Dashboard.tableResponsive();

});
