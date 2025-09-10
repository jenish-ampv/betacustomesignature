Office.onReady();

function checkSignature(event) {
  const userEmail = Office.context.mailbox.userProfile.emailAddress;
  const signatureUrl = 'https://betaapp.customesignature.com/receivesignature/email/' + userEmail;

  fetch(signatureUrl)
    .then(res => res.text())
    .then(signature => {
      if (signature.length) {
        Office.context.mailbox.item.body.setSignatureAsync(
          signature,
          {
            coercionType: "html",
            asyncContext: event,
          },
          addSignatureCallback
        );
      }
    }
  );
}
// Office.initialize = function (reason) {};
// Event handler that updates the signature when the email address in the From field is changed.
function onMessageFromChangedHandler(event) {
    const item = Office.context.mailbox.item;

    // Get the currently selected From account.
    item.from.getAsync({ asyncContext: event }, (result) => {
        if (result.status === Office.AsyncResultStatus.Failed) {
            console.log(result.error.message);
            return;
        }
        const name = result.value.emailAddress;
        const signatureUrl = 'https://betaapp.customesignature.com/receivesignature/email/' + name;
        fetch(signatureUrl)
          .then(res => res.text())
          .then(signature => {
            Office.context.mailbox.item.body.setSignatureAsync(
              signature,
              {
                coercionType: "html",
                asyncContext: event,
              },
              addSignatureCallback
            );

            }
          );
    });
}
// Callback function to add a signature to the mail item.
function addSignatureCallback(result) {
    if (result.status === Office.AsyncResultStatus.Failed) {
      console.log(result.error.message);
      return;
    }

    console.log("Successfully added signature.");
    result.asyncContext.completed();
}
// IMPORTANT: To ensure your add-in is supported in the Outlook client on Windows, remember to
// map the event handler name specified in the manifest's LaunchEvent element to its JavaScript counterpart.
Office.actions.associate("checkSignature", checkSignature);
Office.actions.associate("onMessageFromChangedHandler", onMessageFromChangedHandler);
