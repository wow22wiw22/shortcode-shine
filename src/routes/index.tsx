import { createFileRoute } from "@tanstack/react-router";
import { Widget } from "@/components/widget/Widget";

export const Route = createFileRoute("/")({
  component: Index,
});

function Index() {
  return <Widget />;
}
