import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";
import Home from "./pages/Home.jsx";
import TechDashboard from "./pages/TechDashboard.jsx";
import TechTicketEdit from "./pages/TechTicketEdit.jsx";
import OperationalDashboard from "./pages/OperationalDashboard.jsx";
import ClientTicketList from "./pages/ClientTicketList.jsx";
import ClientTicketDetail from "./pages/ClientTicketDetail.jsx";
import ModuloBancos from "./pages/financeiro/ModuloBancos.jsx";

function EmbeddedTickets() {
    const boot = typeof window !== "undefined" ? window.__TICKETS_BOOT__ : null;
    if (!boot?.screen) return null;
    switch (boot.screen) {
        case "tech_index":
            return <TechDashboard boot={boot} />;
        case "tech_operacional":
            return <OperationalDashboard boot={boot} />;
        case "tech_edit":
            return <TechTicketEdit boot={boot} />;
        case "client_index":
            return <ClientTicketList boot={boot} />;
        case "client_view":
            return <ClientTicketDetail boot={boot} />;
        case "finance_bancos":
        case "finance_remessas":
        case "finance_retorno":
            return <ModuloBancos boot={boot} />;
        default:
            return null;
    }
}

export default function App() {
    if (typeof window !== "undefined" && window.__TICKETS_BOOT__?.screen) {
        return <EmbeddedTickets />;
    }

    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<Home />} />
                <Route
                    path="/tecnico"
                    element={<TechDashboard boot={null} />}
                />
                <Route
                    path="/tecnico/operacional"
                    element={<OperationalDashboard boot={null} />}
                />
                <Route
                    path="/cliente"
                    element={<ClientTicketList boot={null} />}
                />
                <Route
                    path="/cliente/ticket/:id"
                    element={<ClientTicketDetail boot={null} />}
                />
                <Route
                    path="/financeiro"
                    element={<Navigate to="/financeiro/bancos" replace />}
                />
                <Route
                    path="/financeiro/bancos"
                    element={<ModuloBancos boot={null} />}
                />
                <Route
                    path="/financeiro/remessas"
                    element={<ModuloBancos boot={null} />}
                />
                <Route
                    path="/financeiro/retornos"
                    element={<ModuloBancos boot={null} />}
                />
                <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
        </BrowserRouter>
    );
}
