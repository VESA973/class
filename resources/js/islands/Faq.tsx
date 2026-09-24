import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';

type Props = { items: { question: string; answer: string }[] };

export default function Faq({ items }: Props) {
    if (items.length === 0) {
        return null;
    }

    return (
        <Accordion type="single" collapsible defaultValue="faq-0" className="rounded-2xl border border-border bg-card px-5">
            {items.map((item, index) => (
                <AccordionItem key={item.question} value={`faq-${index}`}>
                    <AccordionTrigger className="text-base">{item.question}</AccordionTrigger>
                    <AccordionContent className="text-base leading-relaxed text-muted-foreground">{item.answer}</AccordionContent>
                </AccordionItem>
            ))}
        </Accordion>
    );
}
