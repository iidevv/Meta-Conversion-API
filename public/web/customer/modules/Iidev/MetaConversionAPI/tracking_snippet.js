xcart.bind(
  "fbAddedToCart",
  (_, { type, eventName, parameters, eventIdObject }) => {
    fbq(type, eventName, parameters, eventIdObject);
  }
);
